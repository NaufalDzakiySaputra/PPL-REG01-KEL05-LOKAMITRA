<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\DokumenPersyaratan;
use App\Models\Notifikasi;
use App\Models\RiwayatAktivitas;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerifikasiPendaftaranService
{
    // Role yang dapat mengajukan pendaftaran melalui halaman publik.
    public function rolePendaftar(): array
    {
        return array_values(array_filter(
            array_keys(config('lokamitra.peran_registrasi', [])),
            fn (string $role) => $role !== User::ROLE_PENGUNJUNG
        ));
    }

    // Mengambil data profil berdasarkan role pendaftar.
    public function profil(User $user): mixed
    {
        return match ($user->role) {
            User::ROLE_EO => $user->eo,
            User::ROLE_VENDOR => $user->vendor,
            User::ROLE_PEMDA => $user->pemerintahDaerah,
            User::ROLE_KOMUNITAS => $user->komunitasLokal,
            User::ROLE_AKADEMISI => $user->akademisi,
            User::ROLE_MEDIA => $user->media,
            default => null,
        };
    }

    // Mengembalikan alasan jika pengajuan belum dapat disetujui.
    public function kendalaPersetujuan(User $user): array
    {
        $kendala = [];

        if ($user->status_akun !== User::STATUS_MENUNGGU) {
            $kendala[] = 'Status akun bukan lagi menunggu verifikasi.';
        }

        if (! in_array($user->role, $this->rolePendaftar(), true)) {
            $kendala[] = 'Role akun ini tidak termasuk pendaftaran publik.';
        }

        if (! $this->profil($user)) {
            $kendala[] = 'Data profil pendaftar belum tersedia.';
        }

        $dokumen = $user->dokumenPersyaratan()->get();
        $persyaratanWajib = collect(config("lokamitra.dokumen_registrasi.{$user->role}", []))
            ->where('wajib', true);

        foreach ($persyaratanWajib as $persyaratan) {
            $ada = $dokumen->contains(
                fn (DokumenPersyaratan $item) => $item->jenis_dokumen === $persyaratan['jenis_dokumen']
            );

            if (! $ada) {
                $kendala[] = 'Dokumen wajib belum tersedia: '.$persyaratan['jenis_dokumen'].'.';
            }
        }

        // Semua dokumen yang diunggah, termasuk dokumen opsional, harus diperiksa.
        foreach ($dokumen as $item) {
            if ($item->status_pemeriksaan !== 'disetujui') {
                $kendala[] = 'Dokumen "'.$item->jenis_dokumen.'" belum disetujui.';
            }
        }

        return array_values(array_unique($kendala));
    }

    // Menyetujui akun setelah seluruh data dan dokumen dinyatakan sesuai.
    public function setujuiAkun(User $pengaju, User $admin): void
    {
        DB::transaction(function () use ($pengaju, $admin) {
            $target = User::query()->lockForUpdate()->findOrFail($pengaju->id);
            $adminId = $this->adminProfileId($admin);
            $kendala = $this->kendalaPersetujuan($target);

            if ($kendala !== []) {
                throw ValidationException::withMessages([
                    'verifikasi' => $kendala,
                ]);
            }

            $profil = $this->profil($target);
            $profil->update([
                'admin_id' => $adminId,
                'status_verifikasi' => 'disetujui',
            ]);

            $target->update([
                'status_akun' => User::STATUS_AKTIF,
            ]);

            $this->catatAktivitas(
                $admin,
                'akun_disetujui',
                "Admin menyetujui pendaftaran {$target->name} ({$target->role}), akun ID {$target->id}."
            );

            $this->kirimNotifikasi(
                $target,
                'Pendaftaran Lokamitra Anda telah disetujui. Silakan login untuk menggunakan akun.'
            );
        });
    }

    // Menolak akun beserta profil pendaftar dan menyimpan alasan penolakan.
    public function tolakAkun(User $pengaju, User $admin, string $alasan): void
    {
        DB::transaction(function () use ($pengaju, $admin, $alasan) {
            $target = User::query()->lockForUpdate()->findOrFail($pengaju->id);
            $this->pastikanPengajuanMenunggu($target);
            $adminId = $this->adminProfileId($admin);
            $profil = $this->profil($target);

            if (! $profil) {
                throw ValidationException::withMessages([
                    'pengajuan' => 'Data profil pendaftar tidak ditemukan.',
                ]);
            }

            $profil->update([
                'admin_id' => $adminId,
                'status_verifikasi' => 'ditolak',
            ]);

            $target->update([
                'status_akun' => User::STATUS_DITOLAK,
            ]);

            $this->catatAktivitas(
                $admin,
                'akun_ditolak',
                "Admin menolak pendaftaran {$target->name} ({$target->role}), akun ID {$target->id}. Alasan: {$alasan}"
            );

            $this->kirimNotifikasi(
                $target,
                'Pendaftaran Lokamitra Anda ditolak. Alasan: '.$alasan
            );
        });
    }

    // Menyimpan keputusan Admin terhadap satu dokumen pendaftar.
    public function periksaDokumen(
        User $pengaju,
        DokumenPersyaratan $dokumen,
        User $admin,
        string $status,
        ?string $catatan
    ): void {
        DB::transaction(function () use ($pengaju, $dokumen, $admin, $status, $catatan) {
            $target = User::query()->lockForUpdate()->findOrFail($pengaju->id);
            $this->pastikanPengajuanMenunggu($target);

            $item = DokumenPersyaratan::query()
                ->whereKey($dokumen->dokumen_id)
                ->where('akun_id', $target->id)
                ->lockForUpdate()
                ->firstOrFail();

            $adminId = $this->adminProfileId($admin);

            $item->update([
                'admin_id' => $adminId,
                'status_pemeriksaan' => $status,
                'catatan_admin' => $catatan,
            ]);

            $keputusan = $status === 'disetujui' ? 'menyetujui' : 'menolak';
            $deskripsi = "Admin {$keputusan} dokumen {$item->jenis_dokumen} milik {$target->name} (akun ID {$target->id}).";

            if ($catatan) {
                $deskripsi .= ' Catatan: '.$catatan;
            }

            $this->catatAktivitas(
                $admin,
                $status === 'disetujui' ? 'dokumen_disetujui' : 'dokumen_ditolak',
                $deskripsi
            );

            $pesan = $status === 'disetujui'
                ? "Dokumen {$item->jenis_dokumen} Anda telah disetujui Admin."
                : "Dokumen {$item->jenis_dokumen} Anda belum disetujui Admin. Catatan: {$catatan}. Silakan hubungi Admin untuk tindak lanjut.";

            $this->kirimNotifikasi($target, $pesan);
        });
    }

    private function pastikanPengajuanMenunggu(User $user): void
    {
        if ($user->status_akun !== User::STATUS_MENUNGGU) {
            throw ValidationException::withMessages([
                'pengajuan' => 'Pengajuan ini sudah diproses dan tidak dapat diubah lagi.',
            ]);
        }

        if (! in_array($user->role, $this->rolePendaftar(), true)) {
            throw ValidationException::withMessages([
                'pengajuan' => 'Akun ini bukan pendaftar publik.',
            ]);
        }
    }

    private function adminProfileId(User $admin): int
    {
        if ($admin->role !== User::ROLE_ADMIN) {
            abort(403);
        }

        $adminId = Admin::query()->where('akun_id', $admin->id)->value('admin_id');

        if (! $adminId) {
            throw ValidationException::withMessages([
                'admin' => 'Profil Admin tidak ditemukan. Periksa data akun Admin.',
            ]);
        }

        return (int) $adminId;
    }

    private function catatAktivitas(User $admin, string $jenis, string $deskripsi): void
    {
        RiwayatAktivitas::create([
            'akun_id' => $admin->id,
            'jenis_aktivitas' => $jenis,
            'deskripsi' => $deskripsi,
            'waktu_aktivitas' => now(),
        ]);
    }

    private function kirimNotifikasi(User $penerima, string $pesan): void
    {
        Notifikasi::create([
            'akun_id' => $penerima->id,
            'isi_notifikasi' => $pesan,
            'waktu_dibuat' => now(),
            'status_baca' => 'belum_dibaca',
        ]);
    }
}

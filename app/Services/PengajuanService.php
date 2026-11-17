<?php

namespace App\Services;

use App\Models\DokumenPersyaratan;
use App\Models\User;

class PengajuanService
{
    public function __construct(
        private readonly VerifikasiPendaftaranService $verifikasi
    ) {}

    /**
     * Data daftar pengajuan beserta ringkasan dan filter aktif.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function daftar(array $query): array
    {
        $statusMentah = $query['status'] ?? User::STATUS_MENUNGGU;
        $status = $this->statusDatabase($statusMentah);
        $role = $query['role'] ?? 'semua';
        $kataKunci = trim((string) ($query['q'] ?? ''));

        if (! in_array($status, ['semua', User::STATUS_MENUNGGU, User::STATUS_AKTIF, User::STATUS_DITOLAK], true)) {
            $status = User::STATUS_MENUNGGU;
        }

        if ($role !== 'semua' && ! in_array($role, $this->verifikasi->rolePendaftar(), true)) {
            $role = 'semua';
        }

        $builder = User::query()
            ->whereIn('role', $this->verifikasi->rolePendaftar())
            ->with([
                'eo',
                'vendor',
                'pemerintahDaerah',
                'komunitasLokal',
                'akademisi',
                'media',
            ])
            ->withCount('dokumenPersyaratan')
            ->withCount([
                'dokumenPersyaratan as dokumen_menunggu_count' => fn ($q) => $q->where('status_pemeriksaan', 'menunggu'),
            ]);

        if ($status !== 'semua') {
            $builder->where('status_akun', $status);
        }

        if ($role !== 'semua') {
            $builder->where('role', $role);
        }

        if ($kataKunci !== '') {
            $builder->where(function ($q) use ($kataKunci) {
                $cari = '%'.$kataKunci.'%';

                $q->where('name', 'like', $cari)
                    ->orWhere('email', 'like', $cari)
                    ->orWhereHas('eo', fn ($profil) => $profil->where('nama_eo_organisasi', 'like', $cari))
                    ->orWhereHas('vendor', fn ($profil) => $profil->where('nama_usaha', 'like', $cari))
                    ->orWhereHas('pemerintahDaerah', fn ($profil) => $profil->where('nama_instansi', 'like', $cari))
                    ->orWhereHas('komunitasLokal', fn ($profil) => $profil->where('nama_komunitas', 'like', $cari))
                    ->orWhereHas('akademisi', fn ($profil) => $profil->where('nama_institusi', 'like', $cari))
                    ->orWhereHas('media', fn ($profil) => $profil->where('nama_media', 'like', $cari));
            });
        }

        $pengajuan = $builder
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'nama' => $user->name,
                'email' => $user->email,
                'nomor_telepon' => $user->nomor_telepon,
                'role' => $user->role,
                'role_label' => config("lokamitra.peran_registrasi.{$user->role}"),
                'nama_profil' => $this->namaProfil($user),
                'status_akun' => $user->status_akun,
                'jumlah_dokumen' => $user->dokumen_persyaratan_count,
                'dokumen_menunggu' => $user->dokumen_menunggu_count,
                'tanggal_daftar' => $user->created_at?->toIso8601String(),
            ]);

        $ringkasan = [
            'menunggu' => User::whereIn('role', $this->verifikasi->rolePendaftar())
                ->where('status_akun', User::STATUS_MENUNGGU)->count(),
            'aktif' => User::whereIn('role', $this->verifikasi->rolePendaftar())
                ->where('status_akun', User::STATUS_AKTIF)->count(),
            'ditolak' => User::whereIn('role', $this->verifikasi->rolePendaftar())
                ->where('status_akun', User::STATUS_DITOLAK)->count(),
        ];

        return [
            // Kontrak spec: daftar ringkas dengan status/role dalam bentuk API.
            'data' => collect($pengajuan->items())
                ->map(fn (array $item) => [
                    'id' => $item['id'],
                    'user_name' => $item['nama'],
                    'role' => $this->roleApi($item['role']),
                    'status_verifikasi' => $this->statusApi($item['status_akun']),
                    'created_at' => $item['tanggal_daftar'],
                ])
                ->values()
                ->all(),
            'pengajuan' => $pengajuan,
            'ringkasan' => $ringkasan,
            'filter' => [
                'status' => $status,
                'status_api' => $this->statusApi($status),
                'role' => $role,
                'q' => $kataKunci,
            ],
            'roles' => collect(config('lokamitra.peran_registrasi', []))
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values(),
        ];
    }

    /**
     * Detail satu pengajuan, termasuk dokumen dan kendala persetujuan.
     *
     * @return array<string, mixed>
     */
    public function detail(User $pengajuan): array
    {
        abort_unless(in_array($pengajuan->role, $this->verifikasi->rolePendaftar(), true), 404);

        $pengajuan->load([
            'eo',
            'vendor',
            'pemerintahDaerah',
            'komunitasLokal',
            'akademisi',
            'media',
            'dokumenPersyaratan' => fn ($q) => $q->orderBy('created_at'),
        ]);

        $profil = $this->verifikasi->profil($pengajuan);

        $dokumen = $pengajuan->dokumenPersyaratan->map(fn (DokumenPersyaratan $item) => [
            'id' => $item->dokumen_id,
            'jenis' => $item->jenis_dokumen,
            'nama_file' => $item->nama_file,
            'format' => strtoupper($item->format_file),
            'sifat' => $item->sifat_dokumen,
            'tanggal_upload' => $item->tanggal_upload?->toIso8601String(),
            'status' => $item->status_pemeriksaan,
            'catatan_admin' => $item->catatan_admin,
            'preview_url' => route('api.v1.admin.pengajuan.documents.preview', [$pengajuan, $item], false),
            'download_url' => route('api.v1.admin.pengajuan.documents.download', [$pengajuan, $item], false),
            // File privat: tautan unduh wajib melalui autentikasi, bukan /storage publik.
            'file_url' => route('api.v1.admin.pengajuan.documents.download', [$pengajuan, $item], false),
        ])->values();

        $kendala = $this->verifikasi->kendalaPersetujuan($pengajuan);

        return [
            'pengajuan' => [
                'id' => $pengajuan->id,
                'nama' => $pengajuan->name,
                'email' => $pengajuan->email,
                'nomor_telepon' => $pengajuan->nomor_telepon,
                'role' => $pengajuan->role,
                'role_label' => config("lokamitra.peran_registrasi.{$pengajuan->role}"),
                'status_akun' => $pengajuan->status_akun,
                'status_verifikasi' => $this->statusApi($pengajuan->status_akun),
                'tanggal_daftar' => $pengajuan->created_at?->toIso8601String(),
                'profil' => $this->dataProfil($pengajuan, $profil),
                'dokumen' => $dokumen,
            ],
            'kendala_persetujuan' => $kendala,
            'dapat_disetujui' => $pengajuan->status_akun === User::STATUS_MENUNGGU && $kendala === [],
        ];
    }

    private function namaProfil(User $user): ?string
    {
        $profil = $this->verifikasi->profil($user);

        return match ($user->role) {
            User::ROLE_EO => $profil?->nama_eo_organisasi,
            User::ROLE_VENDOR => $profil?->nama_usaha,
            User::ROLE_PEMDA => $profil?->nama_instansi,
            User::ROLE_KOMUNITAS => $profil?->nama_komunitas,
            User::ROLE_AKADEMISI => $profil?->nama_institusi,
            User::ROLE_MEDIA => $profil?->nama_media,
            default => null,
        };
    }

    /** @return array<int, array<string, string>> */
    private function dataProfil(User $user, mixed $profil): array
    {
        $data = match ($user->role) {
            User::ROLE_EO => [
                'Nama organisasi/EO' => $profil?->nama_eo_organisasi,
                'Website/media sosial' => $profil?->website_media_sosial,
                'Alamat kantor' => $profil?->alamat_kantor,
                'Jenis event yang ditangani' => $profil?->jenis_event_yang_ditangani,
            ],
            User::ROLE_VENDOR => [
                'Nama usaha' => $profil?->nama_usaha,
                'Nama pemilik' => $profil?->nama_pemilik,
                'Alamat usaha' => $profil?->alamat_usaha,
                'Bidang usaha' => $profil?->bidang_usaha,
            ],
            User::ROLE_PEMDA => [
                'Nama instansi' => $profil?->nama_instansi,
                'Jabatan pendaftar' => $profil?->jabatan_pendaftar,
                'Wilayah kerja' => $profil?->wilayah_kerja,
                'Bidang yang ditangani' => $profil?->bidang_yang_ditangani,
            ],
            User::ROLE_KOMUNITAS => [
                'Nama komunitas' => $profil?->nama_komunitas,
                'Perkiraan jumlah anggota' => $profil?->perkiraan_jumlah_anggota,
                'Wilayah kegiatan' => $profil?->wilayah_kegiatan,
                'Bidang kegiatan' => $profil?->bidang_kegiatan,
            ],
            User::ROLE_AKADEMISI => [
                'Nama institusi' => $profil?->nama_institusi,
                'Fakultas/unit/jurusan' => $profil?->fakultas_unit_jurusan,
                'Bidang keahlian' => $profil?->bidang_keahlian,
                'Tautan profil riset' => $profil?->tautan_profil_riset,
            ],
            User::ROLE_MEDIA => [
                'Nama media' => $profil?->nama_media,
                'Pemimpin redaksi' => $profil?->nama_pemimpin_redaksi,
                'Wilayah liputan' => $profil?->wilayah_liputan,
                'Jenis media' => $profil?->jenis_media,
            ],
            default => [],
        };

        return collect($data)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value, $label) => ['label' => $label, 'value' => (string) $value])
            ->values()
            ->all();
    }

    /** Memetakan status database ke nilai kontrak API. */
    private function statusApi(string $status): string
    {
        return match ($status) {
            User::STATUS_AKTIF => 'verified',
            User::STATUS_DITOLAK => 'rejected',
            User::STATUS_MENUNGGU => 'pending',
            default => $status,
        };
    }

    /** Menerima nilai status kontrak API lalu memetakannya ke nilai database. */
    private function statusDatabase(mixed $status): string
    {
        if (! is_string($status)) {
            return User::STATUS_MENUNGGU;
        }

        return match ($status) {
            'pending' => User::STATUS_MENUNGGU,
            'verified' => User::STATUS_AKTIF,
            'rejected' => User::STATUS_DITOLAK,
            default => $status,
        };
    }

    /** Memetakan role database ke alias yang dipakai spesifikasi. */
    private function roleApi(string $role): string
    {
        return match ($role) {
            User::ROLE_VENDOR => 'vendor_umkm',
            User::ROLE_KOMUNITAS => 'komunitas_lokal',
            User::ROLE_PEMDA => 'pemerintah_daerah',
            default => $role,
        };
    }
}

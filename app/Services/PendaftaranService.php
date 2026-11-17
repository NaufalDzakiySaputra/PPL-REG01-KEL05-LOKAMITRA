<?php

namespace App\Services;

use App\Models\Akademisi;
use App\Models\DokumenPersyaratan;
use App\Models\Eo;
use App\Models\KomunitasLokal;
use App\Models\Media;
use App\Models\PemerintahDaerah;
use App\Models\User;
use App\Models\VendorUmkm;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PendaftaranService
{
    /**
     * Membuat akun, profil peran, dan dokumen pendaftaran dalam satu transaksi.
     *
     * @param  array<string, mixed>  $data  Hasil validasi RegisterRequest.
     */
    public function daftarkan(array $data): User
    {
        $documents = $data['documents'] ?? [];

        unset($data['documents'], $data['persetujuan']);

        // Path file dibersihkan jika transaksi database gagal.
        $storedFiles = [];

        try {
            return DB::transaction(function () use ($data, $documents, &$storedFiles) {
                $akun = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'nomor_telepon' => $data['nomor_telepon'],
                    'password' => $data['password'],
                    'role' => $data['role'],
                    'status_akun' => $data['role'] === User::ROLE_PENGUNJUNG
                        ? User::STATUS_AKTIF
                        : User::STATUS_MENUNGGU,
                ]);

                // Membuat profil sesuai peran yang dipilih.
                match ($data['role']) {
                    User::ROLE_EO => Eo::create([
                        'akun_id' => $akun->id,
                        'nama_eo_organisasi' => $data['nama_eo_organisasi'],
                        'website_media_sosial' => $data['website_media_sosial'] ?? null,
                        'alamat_kantor' => $data['alamat_kantor'] ?? null,
                        'jenis_event_yang_ditangani' => $data['jenis_event_yang_ditangani'] ?? null,
                        'status_verifikasi' => Eo::STATUS_MENUNGGU,
                    ]),

                    User::ROLE_VENDOR => VendorUmkm::create([
                        'akun_id' => $akun->id,
                        'nama_usaha' => $data['nama_usaha'],
                        'nama_pemilik' => $data['nama_pemilik'],
                        'alamat_usaha' => $data['alamat_usaha'] ?? null,
                        'bidang_usaha' => $data['bidang_usaha'] ?? null,
                        'status_verifikasi' => VendorUmkm::STATUS_MENUNGGU,
                    ]),

                    User::ROLE_PEMDA => PemerintahDaerah::create([
                        'akun_id' => $akun->id,
                        'nama_instansi' => $data['nama_instansi'],
                        'jabatan_pendaftar' => $data['jabatan_pendaftar'],
                        'wilayah_kerja' => $data['wilayah_kerja'],
                        'bidang_yang_ditangani' => $data['bidang_yang_ditangani'] ?? null,
                        'status_verifikasi' => PemerintahDaerah::STATUS_MENUNGGU,
                    ]),

                    User::ROLE_KOMUNITAS => KomunitasLokal::create([
                        'akun_id' => $akun->id,
                        'nama_komunitas' => $data['nama_komunitas'],
                        'perkiraan_jumlah_anggota' => $data['perkiraan_jumlah_anggota'] ?? null,
                        'wilayah_kegiatan' => $data['wilayah_kegiatan'] ?? null,
                        'bidang_kegiatan' => $data['bidang_kegiatan'],
                        'status_verifikasi' => KomunitasLokal::STATUS_MENUNGGU,
                    ]),

                    User::ROLE_AKADEMISI => Akademisi::create([
                        'akun_id' => $akun->id,
                        'nama_institusi' => $data['nama_institusi'],
                        'fakultas_unit_jurusan' => $data['fakultas_unit_jurusan'] ?? null,
                        'bidang_keahlian' => $data['bidang_keahlian'],
                        'tautan_profil_riset' => $data['tautan_profil_riset'] ?? null,
                        'status_verifikasi' => Akademisi::STATUS_MENUNGGU,
                    ]),

                    User::ROLE_MEDIA => Media::create([
                        'akun_id' => $akun->id,
                        'nama_media' => $data['nama_media'],
                        'nama_pemimpin_redaksi' => $data['nama_pemimpin_redaksi'] ?? null,
                        'wilayah_liputan' => $data['wilayah_liputan'] ?? null,
                        'jenis_media' => $data['jenis_media'],
                        'status_verifikasi' => Media::STATUS_MENUNGGU,
                    ]),

                    // Pengunjung tidak membutuhkan tabel profil tambahan.
                    User::ROLE_PENGUNJUNG => null,
                };

                // Menyimpan dokumen secara privat agar tidak dapat diakses publik.
                $requirements = config("lokamitra.dokumen_registrasi.{$data['role']}", []);

                foreach ($requirements as $requirement) {
                    $file = $documents[$requirement['key']] ?? null;

                    if (! $file) {
                        continue;
                    }

                    $this->simpanDokumen(
                        $akun,
                        $requirement['jenis_dokumen'],
                        $file,
                        $requirement['sifat_dokumen'],
                        $storedFiles
                    );
                }

                return $akun;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedFiles);

            throw $exception;
        }
    }

    /**
     * Menyimpan satu dokumen persyaratan ke storage privat.
     *
     * Dipakai saat registrasi dan saat mitra mengunggah dokumen tambahan
     * lewat `/api/v1/role-profile/dokumen`.
     *
     * @param  array<int, string>|null  $storedFiles  Daftar path untuk dibersihkan jika transaksi gagal.
     */
    public function simpanDokumen(
        User $akun,
        string $jenisDokumen,
        UploadedFile $file,
        ?string $sifatDokumen = null,
        ?array &$storedFiles = null
    ): DokumenPersyaratan {
        $path = $file->store("dokumen-persyaratan/{$akun->id}", 'local');

        if ($storedFiles !== null) {
            $storedFiles[] = $path;
        }

        try {
            return DokumenPersyaratan::create([
                'akun_id' => $akun->id,
                'jenis_dokumen' => $jenisDokumen,
                'nama_file' => $file->getClientOriginalName(),
                'format_file' => strtolower($file->getClientOriginalExtension()),
                'lokasi_file' => $path,
                'sifat_dokumen' => $sifatDokumen ?? $this->sifatDokumen($akun, $jenisDokumen),
                'tanggal_upload' => now(),
                'status_pemeriksaan' => DokumenPersyaratan::STATUS_MENUNGGU,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }
    }

    /** Menentukan sifat dokumen dari konfigurasi peran; default-nya opsional. */
    public function sifatDokumen(User $akun, string $jenisDokumen): string
    {
        $persyaratan = collect(config("lokamitra.dokumen_registrasi.{$akun->role}", []))
            ->firstWhere('jenis_dokumen', $jenisDokumen);

        return $persyaratan['sifat_dokumen'] ?? 'opsional';
    }
}

<?php

namespace Database\Seeders;

use App\Models\Akademisi;
use App\Models\KomunitasLokal;
use App\Models\Media;
use App\Models\PemerintahDaerah;
use App\Models\User;
use App\Models\VendorUmkm;
use Illuminate\Database\Seeder;

class AkunContohSeeder extends Seeder
{
    /**
     * Akun contoh aktif untuk menguji login setiap role selain Admin dan EO.
     * Semua akun menggunakan password: "password".
     */
    public function run(): void
    {
        $akun = $this->buatAkun('vendor@lokamitra.test', 'Vendor Contoh', User::ROLE_VENDOR);
        VendorUmkm::updateOrCreate(['akun_id' => $akun->id], [
            'nama_usaha' => 'Kopi Contoh',
            'nama_pemilik' => 'Vendor Contoh',
            'alamat_usaha' => 'Bandung',
            'bidang_usaha' => 'Konsumsi',
            'status_verifikasi' => VendorUmkm::STATUS_DISETUJUI,
        ]);

        $akun = $this->buatAkun('pemda@lokamitra.test', 'Pemda Contoh', User::ROLE_PEMDA);
        PemerintahDaerah::updateOrCreate(['akun_id' => $akun->id], [
            'nama_instansi' => 'Dinas Pariwisata Contoh',
            'jabatan_pendaftar' => 'Kepala Seksi Perizinan',
            'wilayah_kerja' => 'Kota Bandung',
            'bidang_yang_ditangani' => 'Pariwisata',
            'status_verifikasi' => PemerintahDaerah::STATUS_DISETUJUI,
        ]);

        $akun = $this->buatAkun('komunitas@lokamitra.test', 'Komunitas Contoh', User::ROLE_KOMUNITAS);
        KomunitasLokal::updateOrCreate(['akun_id' => $akun->id], [
            'nama_komunitas' => 'Komunitas Seni Contoh',
            'perkiraan_jumlah_anggota' => 25,
            'wilayah_kegiatan' => 'Kota Bandung',
            'bidang_kegiatan' => 'Seni dan Budaya',
            'status_verifikasi' => KomunitasLokal::STATUS_DISETUJUI,
        ]);

        $akun = $this->buatAkun('akademisi@lokamitra.test', 'Akademisi Contoh', User::ROLE_AKADEMISI);
        Akademisi::updateOrCreate(['akun_id' => $akun->id], [
            'nama_institusi' => 'Universitas Contoh',
            'fakultas_unit_jurusan' => 'Fakultas Pariwisata',
            'bidang_keahlian' => 'Manajemen Event',
            'tautan_profil_riset' => 'https://example.test/profil-riset',
            'status_verifikasi' => Akademisi::STATUS_DISETUJUI,
        ]);

        $akun = $this->buatAkun('media@lokamitra.test', 'Media Contoh', User::ROLE_MEDIA);
        Media::updateOrCreate(['akun_id' => $akun->id], [
            'nama_media' => 'Warta Contoh',
            'nama_pemimpin_redaksi' => 'Pemimpin Redaksi Contoh',
            'wilayah_liputan' => 'Bandung Raya',
            'jenis_media' => 'Online',
            'status_verifikasi' => Media::STATUS_DISETUJUI,
        ]);

        // Akun Pengunjung langsung aktif dan tidak memerlukan profil atau verifikasi Admin.
        $this->buatAkun('pengunjung@lokamitra.test', 'Pengunjung Contoh', User::ROLE_PENGUNJUNG);
    }

    private function buatAkun(string $email, string $nama, string $role): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $nama,
                'password' => 'password',
                'nomor_telepon' => '081234567890',
                'role' => $role,
                'status_akun' => User::STATUS_AKTIF,
                'email_verified_at' => now(),
            ]
        );
    }
}

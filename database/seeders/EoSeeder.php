<?php

namespace Database\Seeders;

use App\Models\Eo;
use App\Models\User;
use Illuminate\Database\Seeder;

class EoSeeder extends Seeder
{
    /**
     * Akun Event Organizer untuk pengembangan (password: "password").
     *  - eo@lokamitra.test         : sudah diverifikasi, bisa login
     *  - eo.menunggu@lokamitra.test: menunggu verifikasi, login ditolak
     */
    public function run(): void
    {
        $this->buatEo('eo@lokamitra.test', 'EO Contoh', 'Contoh Kreasi Event', User::STATUS_AKTIF, Eo::STATUS_DISETUJUI);
        $this->buatEo('eo.menunggu@lokamitra.test', 'EO Menunggu', 'Menunggu Kreasi Event', User::STATUS_MENUNGGU, Eo::STATUS_MENUNGGU);
    }

    private function buatEo(string $email, string $nama, string $organisasi, string $statusAkun, string $statusVerifikasi): void
    {
        $akun = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $nama,
                'password' => 'password',
                'nomor_telepon' => '081234567890',
                'role' => User::ROLE_EO,
                'status_akun' => $statusAkun,
                'email_verified_at' => now(),
            ]
        );

        Eo::updateOrCreate(
            ['akun_id' => $akun->id],
            [
                'nama_eo_organisasi' => $organisasi,
                'status_verifikasi' => $statusVerifikasi,
            ]
        );
    }
}

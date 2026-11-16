<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Akun admin pengembangan (password: "password").
     * Jangan gunakan password contoh ini di production.
     */
    public function run(): void
    {
        $akun = User::updateOrCreate(
            ['email' => 'admin@lokamitra.test'],
            [
                'name' => 'Admin Lokamitra',
                'password' => 'password',
                'nomor_telepon' => '081234567890',
                'role' => User::ROLE_ADMIN,
                'status_akun' => User::STATUS_AKTIF,
                'email_verified_at' => now(),
            ]
        );

        Admin::updateOrCreate(
            ['akun_id' => $akun->id],
            []
        );
    }
}

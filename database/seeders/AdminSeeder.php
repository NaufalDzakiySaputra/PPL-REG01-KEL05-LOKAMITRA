<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Akun Pengelola Platform untuk pengembangan.
     * Login: admin@lokamitra.test / password  (ganti sebelum dipakai sungguhan)
     */
    public function run(): void
    {
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@lokamitra.test'],
            [
                'name' => 'Pengelola Platform',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status_akun' => 'aktif',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $akunId = DB::table('users')->where('email', 'admin@lokamitra.test')->value('id');

        DB::table('admin')->updateOrInsert(
            ['akun_id' => $akunId],
            ['created_at' => now(), 'updated_at' => now()]
        );
    }
}

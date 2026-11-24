<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            EoSeeder::class,
            AkunContohSeeder::class,
            DemoEventSeeder::class, // data demo event, tugas, dan aktivitas untuk dashboard
            ForumSeeder::class, // data demo topik dan tanggapan forum diskusi
        ]);
    }
}

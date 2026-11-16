<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Status verifikasi untuk profil selain EO.
        // Tabel EO sudah memiliki kolom status_verifikasi dari migration awal.
        foreach ([
            'vendor_umkm',
            'pemerintah_daerah',
            'komunitas_lokal',
            'akademisi',
            'media',
        ] as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->string('status_verifikasi', 20)
                    ->default('menunggu')
                    ->index();
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'vendor_umkm',
            'pemerintah_daerah',
            'komunitas_lokal',
            'akademisi',
            'media',
        ] as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->dropColumn('status_verifikasi');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('komunitas_lokal', function (Blueprint $table) {
            $table->id('komunitas_id');
            $table->foreignId('akun_id')->unique()->constrained('users')->cascadeOnDelete();
            // Admin yang memverifikasi profil ini
            $table->foreignId('admin_id')->nullable()->constrained('admin', 'admin_id')->nullOnDelete();
            $table->string('nama_komunitas');
            $table->unsignedInteger('perkiraan_jumlah_anggota')->nullable();
            $table->string('wilayah_kegiatan')->nullable();
            $table->string('bidang_kegiatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('komunitas_lokal');
    }
};

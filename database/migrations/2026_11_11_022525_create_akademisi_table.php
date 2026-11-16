<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akademisi', function (Blueprint $table) {
            $table->id('akademisi_id');
            $table->foreignId('akun_id')->unique()->constrained('users')->cascadeOnDelete();
            // Admin yang memverifikasi profil ini
            $table->foreignId('admin_id')->nullable()->constrained('admin', 'admin_id')->nullOnDelete();
            $table->string('nama_institusi');
            $table->string('fakultas_unit_jurusan')->nullable();
            $table->string('bidang_keahlian')->nullable();
            $table->string('tautan_profil_riset')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akademisi');
    }
};

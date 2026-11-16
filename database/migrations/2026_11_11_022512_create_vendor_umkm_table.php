<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_umkm', function (Blueprint $table) {
            $table->id('vendor_id');
            $table->foreignId('akun_id')->unique()->constrained('users')->cascadeOnDelete();
            // Admin yang memverifikasi profil ini
            $table->foreignId('admin_id')->nullable()->constrained('admin', 'admin_id')->nullOnDelete();
            $table->string('nama_usaha');
            $table->string('alamat_usaha')->nullable();
            $table->string('nama_pemilik');
            $table->string('bidang_usaha')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_umkm');
    }
};

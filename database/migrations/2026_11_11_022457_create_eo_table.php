<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eo', function (Blueprint $table) {
            $table->id('eo_id');
            $table->foreignId('akun_id')->unique()->constrained('users')->cascadeOnDelete();
            // Admin yang memverifikasi profil ini
            $table->foreignId('admin_id')->nullable()->constrained('admin', 'admin_id')->nullOnDelete();
            $table->string('nama_eo_organisasi');
            $table->string('website_media_sosial')->nullable();
            $table->string('alamat_kantor')->nullable();
            $table->string('jenis_event_yang_ditangani')->nullable();
            // menunggu | disetujui | ditolak
            $table->string('status_verifikasi', 20)->default('menunggu')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eo');
    }
};

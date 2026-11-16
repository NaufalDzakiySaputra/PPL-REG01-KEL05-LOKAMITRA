<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id('media_id');
            $table->foreignId('akun_id')->unique()->constrained('users')->cascadeOnDelete();
            // Admin yang memverifikasi profil ini
            $table->foreignId('admin_id')->nullable()->constrained('admin', 'admin_id')->nullOnDelete();
            $table->string('nama_media');
            $table->string('nama_pemimpin_redaksi')->nullable();
            $table->string('wilayah_liputan')->nullable();
            $table->string('jenis_media')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};

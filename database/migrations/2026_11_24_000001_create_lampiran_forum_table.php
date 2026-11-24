<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lampiran_forum', function (Blueprint $table) {
            $table->id('lampiran_id');

            $table->foreignId('pesan_forum_id')
                ->constrained('pesan_forum', 'pesan_forum_id')
                ->cascadeOnDelete();

            $table->foreignId('pengunggah_akun_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->string('nama_file');
            $table->string('lokasi_file');
            $table->string('format_file', 10);
            $table->unsignedBigInteger('ukuran_file');
            $table->dateTime('waktu_upload');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lampiran_forum');
    }
};

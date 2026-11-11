<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keanggotaan_event', function (Blueprint $table) {
            $table->id('keanggotaan_id');
            $table->foreignId('event_id')->constrained('event', 'event_id')->cascadeOnDelete();
            $table->foreignId('akun_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('tanggal_bergabung')->nullable();
            // menunggu | diterima | ditolak
            $table->string('status_keanggotaan', 20)->default('menunggu')->index();
            $table->text('pesan_pengajuan')->nullable();
            $table->timestamp('tanggal_respons')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'akun_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keanggotaan_event');
    }
};

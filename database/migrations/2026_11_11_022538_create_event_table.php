<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event', function (Blueprint $table) {
            $table->id('event_id');
            $table->foreignId('eo_id')->constrained('eo', 'eo_id')->cascadeOnDelete();
            $table->string('nama_event');
            $table->text('deskripsi')->nullable();
            $table->string('lokasi')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            // draft | menunggu_verifikasi | dipublikasikan | ditutup | diarsipkan
            $table->string('status_event', 30)->default('draft')->index();
            $table->string('kategori_event')->nullable();
            $table->text('informasi_regulasi')->nullable();
            $table->string('poster')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('admin', 'admin_id')->nullOnDelete();
            $table->timestamp('tanggal_diverifikasi')->nullable();
            $table->timestamp('tanggal_ditutup')->nullable();
            $table->timestamp('tanggal_diarsipkan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event');
    }
};

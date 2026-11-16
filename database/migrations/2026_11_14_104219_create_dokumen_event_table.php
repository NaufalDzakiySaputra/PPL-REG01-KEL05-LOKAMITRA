
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_event', function (Blueprint $table) {
            $table->id('dokumen_event_id');

            $table->foreignId('event_id')
                ->constrained('event', 'event_id')
                ->cascadeOnDelete();

            $table->foreignId('pengunggah_akun_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->string('nama_dokumen');
            $table->string('lokasi_file');
            $table->dateTime('tanggal_upload');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_event');
    }
};

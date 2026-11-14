
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_persyaratan', function (Blueprint $table) {
            $table->id('dokumen_id');

            $table->foreignId('akun_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->foreignId('admin_id')
                ->nullable()
                ->constrained('admin', 'admin_id')
                ->nullOnDelete();

            $table->string('jenis_dokumen');
            $table->string('nama_file');
            $table->string('format_file');
            $table->string('lokasi_file');
            $table->string('sifat_dokumen');
            $table->dateTime('tanggal_upload');
            $table->string('status_pemeriksaan');
            $table->text('catatan_admin')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_persyaratan');
    }
};

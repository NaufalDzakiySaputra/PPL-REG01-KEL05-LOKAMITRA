
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_aktivitas', function (Blueprint $table) {
            $table->id('riwayat_id');

            $table->foreignId('akun_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->string('jenis_aktivitas');
            $table->text('deskripsi');
            $table->dateTime('waktu_aktivitas');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_aktivitas');
    }
};

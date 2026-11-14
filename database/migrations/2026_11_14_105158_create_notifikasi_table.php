
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id('notifikasi_id');

            $table->foreignId('akun_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->text('isi_notifikasi');
            $table->dateTime('waktu_dibuat');
            $table->string('status_baca');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};

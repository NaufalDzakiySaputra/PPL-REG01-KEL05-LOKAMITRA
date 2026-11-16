
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tugas', function (Blueprint $table) {
            $table->id('tugas_id');

            $table->foreignId('event_id')
                ->constrained('event', 'event_id')
                ->cascadeOnDelete();

            $table->foreignId('penerima_akun_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->dateTime('tanggal_deadline');
            $table->string('status_tugas');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tugas');
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesan_chat', function (Blueprint $table) {
            $table->id('pesan_chat_id');

            $table->foreignId('grup_chat_id')
                ->constrained('grup_chat', 'grup_chat_id')
                ->cascadeOnDelete();

            $table->foreignId('pengirim_akun_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->text('isi_pesan');
            $table->dateTime('waktu_kirim');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesan_chat');
    }
};

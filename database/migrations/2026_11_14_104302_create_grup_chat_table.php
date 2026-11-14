
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grup_chat', function (Blueprint $table) {
            $table->id('grup_chat_id');

            $table->foreignId('event_id')
                ->constrained('event', 'event_id')
                ->cascadeOnDelete();

            $table->string('nama_grup');
            $table->dateTime('tanggal_dibuat');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grup_chat');
    }
};

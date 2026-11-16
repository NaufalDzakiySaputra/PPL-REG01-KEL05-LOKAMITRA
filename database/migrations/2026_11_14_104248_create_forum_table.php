
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum', function (Blueprint $table) {
            $table->id('forum_id');

            $table->foreignId('event_id')
                ->constrained('event', 'event_id')
                ->cascadeOnDelete();

            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->dateTime('tanggal_dibuat');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum');
    }
};

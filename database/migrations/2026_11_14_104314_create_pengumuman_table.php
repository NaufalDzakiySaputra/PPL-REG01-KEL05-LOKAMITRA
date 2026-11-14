
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengumuman', function (Blueprint $table) {
            $table->id('pengumuman_id');

            $table->foreignId('event_id')
                ->constrained('event', 'event_id')
                ->cascadeOnDelete();

            $table->string('judul');
            $table->text('isi_pengumuman');
            $table->dateTime('tanggal_dibuat');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumuman');
    }
};

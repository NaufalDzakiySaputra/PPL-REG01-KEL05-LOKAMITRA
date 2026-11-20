<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kontak_darurat_event', function (Blueprint $table) {
            $table->id('kontak_darurat_event_id');

            $table->foreignId('event_id')
                ->constrained('event', 'event_id')
                ->cascadeOnDelete();

            $table->string('nama');
            $table->string('nomor_telepon');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kontak_darurat_event');
    }
};

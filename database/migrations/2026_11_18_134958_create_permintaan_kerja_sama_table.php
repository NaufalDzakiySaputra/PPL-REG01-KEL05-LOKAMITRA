
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_kerja_sama', function (Blueprint $table) {
            $table->id();

            // Event yang diajukan oleh EO
            $table->foreignId('event_id')
                ->constrained('event', 'event_id')
                ->cascadeOnDelete();

            // Akun stakeholder yang menerima pengajuan
            $table->foreignId('stakeholder_akun_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Pesan dari EO (opsional)
            $table->text('pesan')->nullable();

            // Status permintaan kerja sama
            $table->string('status', 30)
                ->default('menunggu')
                ->index();

            $table->timestamps();

            $table->index(['event_id', 'stakeholder_akun_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_kerja_sama');
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan', function (Blueprint $table) {
            $table->id('laporan_id');

            $table->foreignId('admin_id')
                ->constrained('admin', 'admin_id')
                ->cascadeOnDelete();

            $table->string('jenis_laporan');
            $table->text('isi_laporan');
            $table->dateTime('tanggal_dibuat');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan');
    }
};
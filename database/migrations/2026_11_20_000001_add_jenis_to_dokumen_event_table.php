<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // PBI-035 membedakan "layout" (denah venue) dari "dokumen" pendukung.
    public function up(): void
    {
        Schema::table('dokumen_event', function (Blueprint $table) {
            $table->string('jenis', 20)->default('dokumen')->after('nama_dokumen')->index();
        });
    }

    public function down(): void
    {
        Schema::table('dokumen_event', function (Blueprint $table) {
            $table->dropColumn('jenis');
        });
    }
};

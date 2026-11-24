<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forum', function (Blueprint $table) {
            // Identitas pembuat topik (PBI-043). Nullable agar topik lama tetap aman.
            $table->foreignId('pembuat_akun_id')
                ->nullable()
                ->after('event_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('forum', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pembuat_akun_id');
        });
    }
};

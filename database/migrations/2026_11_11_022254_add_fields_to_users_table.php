<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nomor_telepon', 30)->nullable()->after('email');
            // admin | eo | pemda | vendor | komunitas | akademisi | media
            $table->string('role', 20)->nullable()->index()->after('nomor_telepon');
            // menunggu | aktif | ditolak
            $table->string('status_akun', 20)->default('menunggu')->index()->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nomor_telepon', 'role', 'status_akun']);
        });
    }
};

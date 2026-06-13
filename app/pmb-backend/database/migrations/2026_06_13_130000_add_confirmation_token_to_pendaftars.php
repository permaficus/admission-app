<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah confirmation_token ke pendaftars untuk mengamankan endpoint mutasi
     * public (heregistrasi / konfirmasi-jadwal / reschedule).
     *
     * Tanpa token, format nomor PMB-YYYY-XXXX yang enumerable (9000 kombinasi)
     * memungkinkan IDOR — siapapun yang menebak nomor bisa melakukan mutasi
     * untuk orang lain. Dengan token, attacker juga butuh string acak 32-char.
     */
    public function up(): void
    {
        Schema::table('pendaftars', function (Blueprint $table) {
            $table->string('confirmation_token', 64)->nullable()->unique()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('pendaftars', function (Blueprint $table) {
            $table->dropUnique(['confirmation_token']);
            $table->dropColumn('confirmation_token');
        });
    }
};

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
        Schema::create('jadwal_tes', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->string('jenis', 20); // 'tes_tulis' | 'wawancara'
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('lokasi', 255);
            $table->unsignedInteger('kuota');
            $table->unsignedInteger('kapasitas_terisi')->default(0);
            $table->string('status', 20)->default('aktif'); // 'aktif' | 'dibatalkan'
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tanggal', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_tes');
    }
};

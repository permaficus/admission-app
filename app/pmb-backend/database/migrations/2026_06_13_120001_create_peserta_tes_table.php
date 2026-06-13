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
        Schema::create('peserta_tes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pendaftar_id')
                ->constrained('pendaftars')
                ->cascadeOnDelete();
            $table->foreignId('jadwal_tes_id')
                ->constrained('jadwal_tes')
                ->cascadeOnDelete();
            $table->string('nomor_meja', 20)->nullable();
            $table->string('status_kehadiran', 20)->default('belum'); // 'belum' | 'hadir' | 'tidak_hadir'
            $table->timestamp('confirmed_at')->nullable();
            $table->string('reschedule_status', 20)->default('none'); // 'none' | 'requested' | 'approved' | 'rejected'
            $table->text('reschedule_alasan')->nullable();
            $table->timestamps();

            $table->unique(['pendaftar_id', 'jadwal_tes_id']);
            $table->index('reschedule_status');
            $table->index(['jadwal_tes_id', 'status_kehadiran']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_tes');
    }
};

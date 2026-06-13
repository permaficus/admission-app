<?php

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\JadwalTesController;
use App\Http\Controllers\Api\PendaftarController;
use App\Http\Controllers\Api\PesertaTesController;
use Illuminate\Support\Facades\Route;

/*
 * API Routes — Sistem PMB
 * Semua route di bawah prefix /api secara otomatis
 */

// --- Auth ---
Route::post('/auth/login', [AdminAuthController::class, 'login']);

// --- Publik (tidak butuh auth) ---
Route::post('/pendaftar', [PendaftarController::class, 'store']);
Route::get('/pendaftar/{nomorPendaftaran}', [PendaftarController::class, 'show'])
    ->where('nomorPendaftaran', 'PMB-[0-9]{4}-[0-9]{4}');
Route::post('/pendaftar/{nomorPendaftaran}/heregistrasi', [PendaftarController::class, 'heregistrasi'])
    ->where('nomorPendaftaran', 'PMB-[0-9]{4}-[0-9]{4}');
Route::post('/pendaftar/{nomorPendaftaran}/konfirmasi-jadwal', [PendaftarController::class, 'konfirmasiJadwal'])
    ->where('nomorPendaftaran', 'PMB-[0-9]{4}-[0-9]{4}');
Route::post('/pendaftar/{nomorPendaftaran}/reschedule', [PendaftarController::class, 'ajukanReschedule'])
    ->where('nomorPendaftaran', 'PMB-[0-9]{4}-[0-9]{4}');

// --- Admin (butuh Sanctum token) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AdminAuthController::class, 'logout']);
    Route::get('/pendaftar', [PendaftarController::class, 'index']);
    Route::patch('/pendaftar/{id}/status', [PendaftarController::class, 'updateStatus']);
    Route::get('/statistik', [PendaftarController::class, 'statistik']);
    Route::get('/pendaftar/export/csv', [PendaftarController::class, 'exportCsv']);

    // --- Jadwal Tes (admin) ---
    Route::get('/jadwal-tes', [JadwalTesController::class, 'index']);
    Route::post('/jadwal-tes', [JadwalTesController::class, 'store']);
    Route::get('/jadwal-tes/{id}', [JadwalTesController::class, 'show'])->whereNumber('id');
    Route::delete('/jadwal-tes/{id}', [JadwalTesController::class, 'destroy'])->whereNumber('id');
    Route::post('/jadwal-tes/{id}/assign-auto', [JadwalTesController::class, 'assignAuto'])->whereNumber('id');
    Route::post('/jadwal-tes/{id}/peserta', [JadwalTesController::class, 'assignManual'])->whereNumber('id');

    // --- Peserta Tes (admin + operator) ---
    Route::get('/peserta-tes', [PesertaTesController::class, 'index']);
    Route::post('/peserta-tes/{id}/hadir', [PesertaTesController::class, 'markHadir'])->whereNumber('id');
    Route::post('/peserta-tes/{id}/reschedule/approve', [PesertaTesController::class, 'approveReschedule'])->whereNumber('id');
    Route::post('/peserta-tes/{id}/reschedule/reject', [PesertaTesController::class, 'rejectReschedule'])->whereNumber('id');
});

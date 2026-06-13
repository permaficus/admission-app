<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignPesertaRequest;
use App\Http\Requests\CreateJadwalTesRequest;
use App\Models\JadwalTes;
use App\Models\Pendaftar;
use App\Models\PesertaTes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * JadwalTesController — API endpoint untuk mengelola jadwal_tes (admin).
 */
class JadwalTesController extends Controller
{
    /**
     * Menampilkan semua jadwal tes.
     * GET /api/jadwal-tes
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = JadwalTes::query()->orderBy('tanggal', 'asc')->orderBy('jam_mulai', 'asc');

            if ($request->filled('status')) {
                $query->where('status', $request->string('status'));
            }
            if ($request->filled('tanggal')) {
                $query->whereDate('tanggal', $request->string('tanggal'));
            }

            $jadwalList = $query->get();

            return response()->json([
                'success' => true,
                'data' => $jadwalList,
                'meta' => ['total' => $jadwalList->count()],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data jadwal tes',
            ], 500);
        }
    }

    /**
     * Membuat jadwal tes baru.
     * POST /api/jadwal-tes
     */
    public function store(CreateJadwalTesRequest $request): JsonResponse
    {
        try {
            $jadwal = JadwalTes::create([
                'nama' => $request->nama,
                'jenis' => $request->jenis,
                'tanggal' => $request->tanggal,
                'jam_mulai' => $request->jam_mulai,
                'jam_selesai' => $request->jam_selesai,
                'lokasi' => $request->lokasi,
                'kuota' => $request->kuota,
                'kapasitas_terisi' => 0,
                'status' => JadwalTes::STATUS_AKTIF,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil dibuat',
                'data' => $jadwal,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan jadwal',
            ], 500);
        }
    }

    /**
     * Menampilkan detail satu jadwal + daftar peserta.
     * GET /api/jadwal-tes/{id}
     */
    public function show(int $id): JsonResponse
    {
        $jadwal = JadwalTes::with(['pesertaTes.pendaftar'])->find($id);

        if (!$jadwal) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $jadwal,
        ]);
    }

    /**
     * Membatalkan (soft delete) jadwal.
     * DELETE /api/jadwal-tes/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $jadwal = JadwalTes::find($id);

        if (!$jadwal) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal tidak ditemukan',
            ], 404);
        }

        // Soft delete + tandai sebagai dibatalkan agar UI peserta tahu.
        $jadwal->update(['status' => JadwalTes::STATUS_DIBATALKAN]);
        $jadwal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil dibatalkan',
        ]);
    }

    /**
     * Auto-assign pendaftar Lolos Seleksi yang belum punya jadwal aktif
     * pada tanggal yang sama.
     * POST /api/jadwal-tes/{id}/assign-auto
     * Body opsional: { jalur?: string, prodi?: string }
     */
    public function assignAuto(Request $request, int $id): JsonResponse
    {
        $jadwal = JadwalTes::find($id);

        if (!$jadwal) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal tidak ditemukan',
            ], 404);
        }

        if ($jadwal->status !== JadwalTes::STATUS_AKTIF) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal sudah dibatalkan',
            ], 422);
        }

        $kuotaSisa = $jadwal->kuotaSisa();
        if ($kuotaSisa <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Kuota jadwal sudah penuh',
                'errors' => ['kuota' => ['Tidak ada slot tersisa di jadwal ini.']],
            ], 422);
        }

        $jalur = $request->string('jalur')->toString() ?: null;
        $prodi = $request->string('prodi')->toString() ?: null;

        $assignedCount = 0;

        try {
            DB::transaction(function () use ($jadwal, $jalur, $prodi, $kuotaSisa, &$assignedCount) {
                // Pendaftar yang sudah punya jadwal aktif pada tanggal yang sama.
                $excludedPendaftarIds = PesertaTes::query()
                    ->whereHas('jadwalTes', function ($q) use ($jadwal) {
                        $q->whereDate('tanggal', $jadwal->tanggal);
                    })
                    ->where('reschedule_status', '!=', PesertaTes::RESCHEDULE_APPROVED)
                    ->pluck('pendaftar_id')
                    ->all();

                $candidatesQuery = Pendaftar::query()
                    ->where('status', Pendaftar::STATUS_LOLOS)
                    ->whereNotIn('id', $excludedPendaftarIds);

                if ($jalur) {
                    $candidatesQuery->where('jalur', $jalur);
                }
                if ($prodi) {
                    $candidatesQuery->where('prodi', $prodi);
                }

                $candidates = $candidatesQuery->limit($kuotaSisa)->get();

                foreach ($candidates as $pendaftar) {
                    PesertaTes::create([
                        'pendaftar_id' => $pendaftar->id,
                        'jadwal_tes_id' => $jadwal->id,
                        'status_kehadiran' => PesertaTes::STATUS_BELUM,
                        'reschedule_status' => PesertaTes::RESCHEDULE_NONE,
                    ]);
                    $assignedCount++;
                }

                $jadwal->increment('kapasitas_terisi', $assignedCount);
            });

            return response()->json([
                'success' => true,
                'message' => "Berhasil meng-assign {$assignedCount} peserta",
                'data' => [
                    'jumlah_assigned' => $assignedCount,
                    'kuota_sisa' => $jadwal->fresh()->kuotaSisa(),
                    'jadwal' => $jadwal->fresh(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan auto-assign peserta',
            ], 500);
        }
    }

    /**
     * Assign manual satu pendaftar ke jadwal_tes.
     * POST /api/jadwal-tes/{id}/peserta
     */
    public function assignManual(AssignPesertaRequest $request, int $id): JsonResponse
    {
        $jadwal = JadwalTes::find($id);

        if (!$jadwal) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal tidak ditemukan',
            ], 404);
        }

        if ($jadwal->status !== JadwalTes::STATUS_AKTIF) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal sudah dibatalkan',
            ], 422);
        }

        $pendaftar = Pendaftar::where('nomor_pendaftaran', $request->nomor_pendaftaran)->first();

        if (!$pendaftar) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor pendaftaran tidak ditemukan',
            ], 404);
        }

        if (!$pendaftar->isLolosSeleksi()) {
            return response()->json([
                'success' => false,
                'message' => 'Pendaftar belum dinyatakan lolos seleksi',
                'errors' => ['nomor_pendaftaran' => ['Hanya pendaftar berstatus Lolos Seleksi yang dapat di-assign.']],
            ], 422);
        }

        if ($jadwal->kuotaSisa() <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Kuota jadwal sudah penuh',
            ], 422);
        }

        // Cek pendaftar sudah punya jadwal aktif di tanggal yang sama.
        $existing = PesertaTes::where('pendaftar_id', $pendaftar->id)
            ->whereHas('jadwalTes', function ($q) use ($jadwal) {
                $q->whereDate('tanggal', $jadwal->tanggal);
            })
            ->where('reschedule_status', '!=', PesertaTes::RESCHEDULE_APPROVED)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta sudah terdaftar di jadwal lain pada tanggal yang sama',
            ], 422);
        }

        try {
            $peserta = DB::transaction(function () use ($pendaftar, $jadwal, $request) {
                $peserta = PesertaTes::create([
                    'pendaftar_id' => $pendaftar->id,
                    'jadwal_tes_id' => $jadwal->id,
                    'nomor_meja' => $request->nomor_meja,
                    'status_kehadiran' => PesertaTes::STATUS_BELUM,
                    'reschedule_status' => PesertaTes::RESCHEDULE_NONE,
                ]);
                $jadwal->increment('kapasitas_terisi');
                return $peserta;
            });

            return response()->json([
                'success' => true,
                'message' => 'Peserta berhasil di-assign ke jadwal',
                'data' => $peserta->fresh(['pendaftar', 'jadwalTes']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal meng-assign peserta',
            ], 500);
        }
    }
}

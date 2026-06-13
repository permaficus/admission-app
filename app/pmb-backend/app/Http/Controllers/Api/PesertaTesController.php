<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarkHadirRequest;
use App\Models\JadwalTes;
use App\Models\PesertaTes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * PesertaTesController — operasi peserta (admin + operator).
 */
class PesertaTesController extends Controller
{
    /**
     * List peserta dengan filter opsional.
     * GET /api/peserta-tes?reschedule_status=requested&jadwal_tes_id=&status_kehadiran=
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = PesertaTes::with(['pendaftar', 'jadwalTes'])->orderByDesc('created_at');

            if ($request->filled('reschedule_status')) {
                $query->where('reschedule_status', $request->string('reschedule_status'));
            }
            if ($request->filled('jadwal_tes_id')) {
                $query->where('jadwal_tes_id', $request->integer('jadwal_tes_id'));
            }
            if ($request->filled('status_kehadiran')) {
                $query->where('status_kehadiran', $request->string('status_kehadiran'));
            }

            $pesertaList = $query->get();

            return response()->json([
                'success' => true,
                'data'    => $pesertaList,
                'meta'    => ['total' => $pesertaList->count()],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data peserta',
            ], 500);
        }
    }

    /**
     * Tandai kehadiran peserta (operator).
     * POST /api/peserta-tes/{id}/hadir
     */
    public function markHadir(MarkHadirRequest $request, int $id): JsonResponse
    {
        $peserta = PesertaTes::find($id);

        if (!$peserta) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta tidak ditemukan',
            ], 404);
        }

        $peserta->update(['status_kehadiran' => $request->status_kehadiran]);

        return response()->json([
            'success' => true,
            'message' => 'Status kehadiran berhasil diperbarui',
            'data'    => $peserta->fresh(['pendaftar', 'jadwalTes']),
        ]);
    }

    /**
     * Approve permintaan reschedule + assign ulang ke jadwal baru.
     * POST /api/peserta-tes/{id}/reschedule/approve
     * Body: { jadwal_tes_id_baru: int }
     */
    public function approveReschedule(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'jadwal_tes_id_baru' => 'required|integer|exists:jadwal_tes,id',
        ], [
            'jadwal_tes_id_baru.required' => 'Jadwal baru wajib dipilih.',
            'jadwal_tes_id_baru.exists'   => 'Jadwal baru tidak ditemukan.',
        ]);

        $peserta = PesertaTes::with('jadwalTes')->find($id);

        if (!$peserta) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta tidak ditemukan',
            ], 404);
        }

        if ($peserta->reschedule_status !== PesertaTes::RESCHEDULE_REQUESTED) {
            return response()->json([
                'success' => false,
                'message' => 'Permintaan reschedule tidak dalam status menunggu',
            ], 422);
        }

        $jadwalBaru = JadwalTes::find($request->integer('jadwal_tes_id_baru'));
        if (!$jadwalBaru) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal baru tidak ditemukan',
            ], 404);
        }

        if ($jadwalBaru->kuotaSisa() <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Kuota jadwal baru sudah penuh',
            ], 422);
        }

        try {
            $pesertaBaru = DB::transaction(function () use ($peserta, $jadwalBaru) {
                $peserta->update(['reschedule_status' => PesertaTes::RESCHEDULE_APPROVED]);

                if ($peserta->jadwalTes) {
                    $peserta->jadwalTes->decrement('kapasitas_terisi');
                }

                $pesertaBaru = PesertaTes::create([
                    'pendaftar_id'      => $peserta->pendaftar_id,
                    'jadwal_tes_id'     => $jadwalBaru->id,
                    'status_kehadiran'  => PesertaTes::STATUS_BELUM,
                    'reschedule_status' => PesertaTes::RESCHEDULE_NONE,
                ]);

                $jadwalBaru->increment('kapasitas_terisi');

                return $pesertaBaru;
            });

            return response()->json([
                'success' => true,
                'message' => 'Reschedule disetujui dan peserta dipindahkan ke jadwal baru',
                'data'    => $pesertaBaru->fresh(['pendaftar', 'jadwalTes']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyetujui reschedule',
            ], 500);
        }
    }

    /**
     * Reject permintaan reschedule (peserta tetap di jadwal lama).
     * POST /api/peserta-tes/{id}/reschedule/reject
     */
    public function rejectReschedule(int $id): JsonResponse
    {
        $peserta = PesertaTes::find($id);

        if (!$peserta) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta tidak ditemukan',
            ], 404);
        }

        if ($peserta->reschedule_status !== PesertaTes::RESCHEDULE_REQUESTED) {
            return response()->json([
                'success' => false,
                'message' => 'Permintaan reschedule tidak dalam status menunggu',
            ], 422);
        }

        $peserta->update(['reschedule_status' => PesertaTes::RESCHEDULE_REJECTED]);

        return response()->json([
            'success' => true,
            'message' => 'Permintaan reschedule ditolak',
            'data'    => $peserta->fresh(['pendaftar', 'jadwalTes']),
        ]);
    }
}

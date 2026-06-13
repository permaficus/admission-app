<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model PesertaTes — assignment seorang pendaftar ke satu jadwal_tes
 */
class PesertaTes extends Model
{
    protected $table = 'peserta_tes';

    const STATUS_BELUM = 'belum';
    const STATUS_HADIR = 'hadir';
    const STATUS_TIDAK_HADIR = 'tidak_hadir';

    const STATUS_KEHADIRAN_LIST = [
        self::STATUS_BELUM,
        self::STATUS_HADIR,
        self::STATUS_TIDAK_HADIR,
    ];

    const RESCHEDULE_NONE = 'none';
    const RESCHEDULE_REQUESTED = 'requested';
    const RESCHEDULE_APPROVED = 'approved';
    const RESCHEDULE_REJECTED = 'rejected';

    const RESCHEDULE_LIST = [
        self::RESCHEDULE_NONE,
        self::RESCHEDULE_REQUESTED,
        self::RESCHEDULE_APPROVED,
        self::RESCHEDULE_REJECTED,
    ];

    protected $fillable = [
        'pendaftar_id',
        'jadwal_tes_id',
        'nomor_meja',
        'status_kehadiran',
        'confirmed_at',
        'reschedule_status',
        'reschedule_alasan',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    /**
     * Relasi: peserta milik satu pendaftar.
     */
    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(Pendaftar::class, 'pendaftar_id');
    }

    /**
     * Relasi: peserta milik satu jadwal_tes.
     */
    public function jadwalTes(): BelongsTo
    {
        return $this->belongsTo(JadwalTes::class, 'jadwal_tes_id');
    }
}

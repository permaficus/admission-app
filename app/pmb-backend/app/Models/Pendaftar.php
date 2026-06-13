<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Pendaftar — merepresentasikan satu data calon mahasiswa PMB
 */
class Pendaftar extends Model
{
    const STATUS_MENUNGGU = 'Menunggu';
    const STATUS_LOLOS = 'Lolos Seleksi';
    const STATUS_TIDAK_LOLOS = 'Tidak Lolos';

    const STATUS_LIST = [
        self::STATUS_MENUNGGU,
        self::STATUS_LOLOS,
        self::STATUS_TIDAK_LOLOS,
    ];

    protected $fillable = [
        'nomor_pendaftaran',
        'nama',
        'nomor_hp',
        'email',
        'asal_sekolah',
        'prodi',
        'jalur',
        'status',
        'heregistrasi_at',
    ];

    protected $casts = [
        'heregistrasi_at' => 'datetime',
    ];

    /**
     * Relasi: satu pendaftar dapat memiliki banyak entri peserta_tes.
     * Active assignment yang efektif adalah row dengan reschedule_status != 'approved'.
     */
    public function pesertaTes(): HasMany
    {
        return $this->hasMany(PesertaTes::class, 'pendaftar_id');
    }

    /**
     * Cek apakah pendaftar berstatus 'Lolos Seleksi' (syarat untuk di-assign ke jadwal_tes).
     */
    public function isLolosSeleksi(): bool
    {
        return $this->status === self::STATUS_LOLOS;
    }
}

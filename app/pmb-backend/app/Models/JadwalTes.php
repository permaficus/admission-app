<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model JadwalTes — sesi tes seleksi atau wawancara PMB
 */
class JadwalTes extends Model
{
    use SoftDeletes;

    protected $table = 'jadwal_tes';

    const JENIS_TES_TULIS = 'tes_tulis';
    const JENIS_WAWANCARA = 'wawancara';

    const JENIS_LIST = [
        self::JENIS_TES_TULIS,
        self::JENIS_WAWANCARA,
    ];

    const STATUS_AKTIF = 'aktif';
    const STATUS_DIBATALKAN = 'dibatalkan';

    const STATUS_LIST = [
        self::STATUS_AKTIF,
        self::STATUS_DIBATALKAN,
    ];

    protected $fillable = [
        'nama',
        'jenis',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'lokasi',
        'kuota',
        'kapasitas_terisi',
        'status',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'kuota' => 'integer',
        'kapasitas_terisi' => 'integer',
    ];

    /**
     * Relasi: satu jadwal memiliki banyak peserta.
     */
    public function pesertaTes(): HasMany
    {
        return $this->hasMany(PesertaTes::class, 'jadwal_tes_id');
    }

    /**
     * Kuota tersisa = kuota - kapasitas_terisi.
     */
    public function kuotaSisa(): int
    {
        return max(0, $this->kuota - $this->kapasitas_terisi);
    }
}

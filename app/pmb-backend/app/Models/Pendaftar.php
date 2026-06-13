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

    /**
     * SECURITY: `status` sengaja TIDAK ada di $fillable. Status hanya boleh
     * diubah oleh admin via PendaftarController.updateStatus (yang men-set
     * eksplisit, bukan mass-assignment). Mencegah mass-assignment attack di
     * mana attacker submit body { status: 'Lolos Seleksi' } via endpoint
     * apapun yang lupa filter input.
     */
    protected $fillable = [
        'nomor_pendaftaran',
        'nama',
        'nomor_hp',
        'email',
        'asal_sekolah',
        'prodi',
        'jalur',
        'heregistrasi_at',
        'confirmation_token',
    ];

    /**
     * Token tidak pernah masuk ke serializer default (response API). Hanya
     * di-return secara eksplisit pada response /api/pendaftar (POST store).
     */
    protected $hidden = [
        'confirmation_token',
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

<?php

namespace Database\Seeders;

use App\Models\JadwalTes;
use Illuminate\Database\Seeder;

/**
 * Seed beberapa jadwal_tes demo untuk demonstrasi modul penjadwalan.
 */
class JadwalTesSeeder extends Seeder
{
    public function run(): void
    {
        $jadwal = [
            [
                'nama'             => 'Tes Tulis Gelombang 1',
                'jenis'            => JadwalTes::JENIS_TES_TULIS,
                'tanggal'          => now()->addDays(7)->toDateString(),
                'jam_mulai'        => '09:00',
                'jam_selesai'      => '11:00',
                'lokasi'           => 'Gedung A Lantai 2, Ruang 201',
                'kuota'            => 30,
                'kapasitas_terisi' => 0,
                'status'           => JadwalTes::STATUS_AKTIF,
            ],
            [
                'nama'             => 'Wawancara Beasiswa',
                'jenis'            => JadwalTes::JENIS_WAWANCARA,
                'tanggal'          => now()->addDays(10)->toDateString(),
                'jam_mulai'        => '13:00',
                'jam_selesai'      => '16:00',
                'lokasi'           => 'Gedung B Lantai 1, Ruang Sidang',
                'kuota'            => 15,
                'kapasitas_terisi' => 0,
                'status'           => JadwalTes::STATUS_AKTIF,
            ],
            [
                'nama'             => 'Tes Tulis Susulan',
                'jenis'            => JadwalTes::JENIS_TES_TULIS,
                'tanggal'          => now()->addDays(14)->toDateString(),
                'jam_mulai'        => '08:00',
                'jam_selesai'      => '10:00',
                'lokasi'           => 'Gedung A Lantai 3, Ruang 305',
                'kuota'            => 20,
                'kapasitas_terisi' => 0,
                'status'           => JadwalTes::STATUS_AKTIF,
            ],
        ];

        foreach ($jadwal as $row) {
            JadwalTes::create($row);
        }
    }
}

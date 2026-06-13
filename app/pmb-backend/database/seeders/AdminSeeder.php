<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * AdminSeeder — buat akun admin default untuk login panel PMB.
 *
 * SECURITY: password TIDAK lagi di-hardcode. Sumber prioritas:
 *   1. env('ADMIN_PASSWORD') — wajib di-set di production
 *   2. fallback: random 24-char string yang dicetak ke console (development saja)
 *
 * Operator wajib mencatat password yang dicetak saat seeding pertama. Kalau
 * ADMIN_PASSWORD sudah di-set di .env, output console tidak akan menampilkan
 * password dan hanya tampil pesan "ADMIN_PASSWORD env consumed".
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $envPassword = env('ADMIN_PASSWORD');
        $generated = false;

        if (empty($envPassword)) {
            $envPassword = Str::random(24);
            $generated = true;
        }

        User::updateOrCreate(
            ['email' => 'admin@pmb.local'],
            [
                'name'     => 'admin',
                'email'    => 'admin@pmb.local',
                'password' => Hash::make($envPassword),
            ]
        );

        if ($generated) {
            $this->command?->warn('Admin password tidak di-set di env. Generated sementara:');
            $this->command?->line("    {$envPassword}");
            $this->command?->warn('Catat password di atas — TIDAK akan ditampilkan ulang. Untuk production set ADMIN_PASSWORD di .env.');
        } else {
            $this->command?->info('Admin di-seed dengan password dari ADMIN_PASSWORD env.');
        }
    }
}

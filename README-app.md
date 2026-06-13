# PMB Jadwal Tes — Bonus 6 Implementation

Implementasi modul **Penjadwalan Tes Seleksi & Wawancara PMB** sebagai pengembangan lanjutan dari aplikasi PMB yang sudah ada (`~/Documents/admission-app/`). Mengikuti `devplan-suryo.md` di repository ini.

**Stack:** React 18 + Vite + Tailwind (`app/pmb-frontend/`) + Laravel 12 + Sanctum + SQLite (`app/pmb-backend/`). Sesuai stack sistem lama — **tidak ada library tambahan** yang ditambahkan ke `composer.json` atau `package.json`.

---

## Cara Menjalankan

### Backend (Laravel)

```bash
cd app/pmb-backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan serve  # → http://127.0.0.1:8000
```

Seeder otomatis akan membuat 3 jadwal demo (`Tes Tulis Gelombang 1`, `Wawancara Beasiswa`, `Tes Tulis Susulan`) dan beberapa pendaftar contoh.

### Frontend (React + Vite)

```bash
cd app/pmb-frontend
npm install
npm run dev  # → http://localhost:5173
```

### Akses

| URL | Halaman | Akses |
|---|---|---|
| `http://localhost:5173/` | Form pendaftaran + cek status (publik) | Tanpa login |
| `http://localhost:5173/admin` | Dashboard admin + Jadwal Tes | Login: `admin` / `pmb2025` |
| `http://localhost:5173/operator` | Check-in lapangan | Tidak butuh login tapi butuh token Sanctum aktif di sessionStorage (lewat halaman /admin) untuk fitur tandai kehadiran |

---

## Fitur Baru yang Sudah Berjalan (Modul Jadwal Tes)

✅ **Admin: kelola jadwal_tes**
- Lihat daftar jadwal di tab "Jadwal Tes Seleksi" pada halaman /admin
- Buat jadwal baru lewat form (`+ Buat Jadwal`) — input nama, jenis (tes_tulis/wawancara), tanggal, jam mulai/selesai, lokasi, kuota
- Lihat kuota terisi vs total per jadwal
- Tombol "Auto-Assign" untuk meng-assign semua pendaftar berstatus "Lolos Seleksi" yang belum punya jadwal aktif pada tanggal yang sama

✅ **Admin: kelola permintaan reschedule**
- Section "Permintaan Reschedule" muncul otomatis jika ada peserta yang mengajukan
- Tombol Approve (memindahkan peserta ke jadwal lain dengan kuota tersisa) dan Reject

✅ **Calon Mahasiswa: lihat & konfirmasi jadwal**
- Cek status di halaman publik → jika berstatus "Lolos Seleksi" dan sudah di-assign, kartu jadwal otomatis tampil
- Tombol "Konfirmasi Akan Hadir" — setelah klik, lokasi dan nomor meja muncul (sebelum konfirmasi, kedua field tersembunyi sebagai defense-in-depth)
- Tombol "Minta Reschedule" — buka form alasan (min 10 karakter), submit menunggu approval admin
- Status `reschedule_status` ditampilkan dengan badge berwarna (kuning untuk requested, hijau untuk approved, merah untuk rejected)

✅ **Operator: check-in lapangan**
- Halaman `/operator` — input nomor pendaftaran → tampilkan jadwal hari ini
- Dua tombol besar: "Tandai Hadir" (hijau) dan "Tandai Tidak Hadir" (merah)
- Mobile-friendly (lebar minimum 375px)

✅ **Sistem otomatis**
- Constraint UNIQUE `(pendaftar_id, jadwal_tes_id)` mencegah double-assignment
- Service layer mencegah pendaftar di-assign ke dua jadwal pada tanggal yang sama
- Validasi `status = 'Lolos Seleksi'` di semua endpoint assignment
- Sensitive data (lokasi + nomor_meja) hanya muncul di response API setelah `confirmed_at` terisi

---

## Fitur yang Belum (Phase 2+)

❌ **Email/WhatsApp notification** — MVP pakai pull (peserta perlu buka halaman cek status). Push notification ke kanal eksternal masuk roadmap berikutnya.

❌ **Cron reminder** — Laravel Scheduler config sudah dibahas di devplan namun belum di-wire ke job class produksi. Saat ini status dihitung on-read.

❌ **Operator role formal** — halaman `/operator` saat ini bergantung pada Sanctum token admin di sessionStorage. Roadmap: tambahkan role `operator` di tabel users + middleware terpisah.

❌ **QR scanner di Operator** — saat ini input nomor pendaftaran via keyboard. Integrasi barcode/QR scanner di Phase 2.

---

## Konfirmasi Tidak Ada Regresi

Semua fitur lama tetap berjalan tanpa perubahan:

- ✅ Form pendaftaran online (`POST /api/pendaftar`)
- ✅ Generate nomor pendaftaran otomatis (`PMB-YYYY-XXXX`)
- ✅ Cek status pendaftaran via nomor (`GET /api/pendaftar/{nomor}`) — endpoint ini di-extend untuk menyertakan field `jadwal_tes` (nullable) tapi field lama tetap ada (backward-compatible)
- ✅ Login admin dengan Sanctum (`POST /api/auth/login`)
- ✅ Dashboard admin dengan statistik per prodi dan jalur (`GET /api/statistik`)
- ✅ Export CSV (`GET /api/pendaftar/export/csv`)
- ✅ Tombol heregistrasi untuk pendaftar yang lolos (`POST /api/pendaftar/{nomor}/heregistrasi`)
- ✅ Ubah status pendaftar (`PATCH /api/pendaftar/{id}/status`)

Tabel `pendaftars` dan `users` **tidak diubah** (hanya ditambahkan FK target dari tabel baru `peserta_tes`). Tidak ada `ALTER TABLE` di tabel lama.

---

## Endpoint API Baru

### Publik (tanpa Sanctum)
- `POST /api/pendaftar/{nomorPendaftaran}/konfirmasi-jadwal` — peserta konfirmasi kehadiran
- `POST /api/pendaftar/{nomorPendaftaran}/reschedule` — peserta minta reschedule (body: `{ alasan: string min 10 char }`)
- `GET /api/pendaftar/{nomorPendaftaran}` (existing endpoint, di-extend) — response sekarang menyertakan field `jadwal_tes` (nullable)

### Admin (Sanctum required)
- `GET /api/jadwal-tes` — list semua jadwal
- `POST /api/jadwal-tes` — buat jadwal baru
- `GET /api/jadwal-tes/{id}` — detail jadwal + peserta
- `DELETE /api/jadwal-tes/{id}` — batalkan (soft delete)
- `POST /api/jadwal-tes/{id}/assign-auto` — auto-assign pendaftar Lolos Seleksi (body opsional: `{ jalur?, prodi? }`)
- `POST /api/jadwal-tes/{id}/peserta` — assign manual (body: `{ nomor_pendaftaran, nomor_meja? }`)
- `GET /api/peserta-tes?reschedule_status=requested` — list permintaan reschedule
- `POST /api/peserta-tes/{id}/hadir` — tandai kehadiran (body: `{ status_kehadiran: 'hadir' | 'tidak_hadir' }`)
- `POST /api/peserta-tes/{id}/reschedule/approve` — approve reschedule (body: `{ jadwal_tes_id_baru: int }`)
- `POST /api/peserta-tes/{id}/reschedule/reject` — reject reschedule

---

## Struktur Tabel Baru

### `jadwal_tes`
| Kolom | Tipe | Constraint |
|---|---|---|
| id | bigint | PK auto |
| nama | string(150) | NOT NULL |
| jenis | string(20) | NOT NULL (tes_tulis \| wawancara) |
| tanggal | date | NOT NULL |
| jam_mulai | time | NOT NULL |
| jam_selesai | time | NOT NULL (must > jam_mulai) |
| lokasi | string(255) | NOT NULL |
| kuota | uint | NOT NULL, min 1 |
| kapasitas_terisi | uint | NOT NULL, default 0 |
| status | string(20) | NOT NULL, default 'aktif' |
| timestamps + softDeletes | | |

Indexes: `(tanggal, status)`, `status`.

### `peserta_tes`
| Kolom | Tipe | Constraint |
|---|---|---|
| id | bigint | PK auto |
| pendaftar_id | bigint | FK → pendaftars.id ON DELETE CASCADE |
| jadwal_tes_id | bigint | FK → jadwal_tes.id ON DELETE CASCADE |
| nomor_meja | string(20) | NULLABLE |
| status_kehadiran | string(20) | NOT NULL, default 'belum' |
| confirmed_at | timestamp | NULLABLE |
| reschedule_status | string(20) | NOT NULL, default 'none' |
| reschedule_alasan | text | NULLABLE |
| timestamps | | |

Unique: `(pendaftar_id, jadwal_tes_id)`. Indexes: `reschedule_status`, `(jadwal_tes_id, status_kehadiran)`.

---

## Tampilan Modul Penjadwalan (Deskripsi Singkat)

- **Cek Status (Home)** — kartu biru muda muncul di bawah info pendaftar dengan label "📅 Jadwal Tes Anda". Berisi nama sesi, jenis, tanggal (format Indonesia), jam mulai-selesai WIB, dan badge status kehadiran. Tombol "✓ Konfirmasi Akan Hadir" hijau muncul jika belum dikonfirmasi; setelah konfirmasi, kartu otomatis menampilkan lokasi dan nomor meja. Tombol "Minta Reschedule" abu-abu muncul jika belum konfirmasi dan belum ada permintaan reschedule sebelumnya.
- **Operator (/operator)** — header amber-500, input nomor pendaftaran besar (uppercase + monospace), tombol "Cek" biru. Hasil: card biru dengan jadwal hari ini, dua tombol besar "✓ Tandai Hadir" (hijau) dan "✗ Tidak Hadir" (merah) — disabled jika status saat ini sudah sesuai.
- **Admin (/admin)** — di atas tabel pendaftar existing, ada section "Jadwal Tes Seleksi" baru dengan tombol "+ Buat Jadwal" biru. Tabel jadwal menampilkan nama, jenis, tanggal, waktu, kuota (X/Y), badge status. Setiap baris punya tombol "Auto-Assign". Section "⏳ Permintaan Reschedule" otomatis muncul jika ada permintaan menunggu, dengan tombol Approve/Reject per item.

---

*Implementasi mengikuti devplan-suryo.md di repository ini. Untuk detail desain dan trade-offs lihat file devplan-suryo.md Bagian 1-5.*

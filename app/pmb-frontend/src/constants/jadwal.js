/**
 * Konstanta domain modul Penjadwalan Tes
 * Sumber kebenaran sinkron dengan backend (Model JadwalTes + PesertaTes).
 */

export const JENIS_TES_LIST = ['tes_tulis', 'wawancara'];

export const JENIS_TES_LABELS = {
  tes_tulis: 'Tes Tulis',
  wawancara: 'Wawancara',
};

export const STATUS_JADWAL_LIST = ['aktif', 'dibatalkan'];

export const STATUS_JADWAL_LABELS = {
  aktif: 'Aktif',
  dibatalkan: 'Dibatalkan',
};

export const STATUS_JADWAL_COLORS = {
  aktif: 'bg-green-100 text-green-800',
  dibatalkan: 'bg-red-100 text-red-800',
};

export const STATUS_KEHADIRAN_LIST = ['belum', 'hadir', 'tidak_hadir'];

export const STATUS_KEHADIRAN_LABELS = {
  belum: 'Belum',
  hadir: 'Hadir',
  tidak_hadir: 'Tidak Hadir',
};

export const STATUS_KEHADIRAN_COLORS = {
  belum: 'bg-yellow-100 text-yellow-800',
  hadir: 'bg-green-100 text-green-800',
  tidak_hadir: 'bg-red-100 text-red-800',
};

export const RESCHEDULE_STATUS_LIST = ['none', 'requested', 'approved', 'rejected'];

export const RESCHEDULE_STATUS_LABELS = {
  none: 'Tidak Ada Permintaan',
  requested: 'Menunggu Persetujuan',
  approved: 'Disetujui',
  rejected: 'Ditolak',
};

export const RESCHEDULE_STATUS_COLORS = {
  none: 'bg-slate-100 text-slate-700',
  requested: 'bg-yellow-100 text-yellow-800',
  approved: 'bg-green-100 text-green-800',
  rejected: 'bg-red-100 text-red-800',
};

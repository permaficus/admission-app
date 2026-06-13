export const PRODI_LIST = [
  'Teknik Informatika',
  'Sistem Informasi',
  'Manajemen Bisnis',
  'Akuntansi',
];

export const JALUR_LIST = ['SNBT', 'Mandiri', 'Prestasi'];

export const STATUS_LIST = ['Menunggu', 'Lolos Seleksi', 'Tidak Lolos'];

export const LOCALSTORAGE_KEY = 'pmb_pendaftar';

// SECURITY: localStorage key untuk kode konfirmasi peserta. Token disimpan
// setelah registrasi sukses dan dipakai ulang ketika peserta membuka tab
// "Cek Status" di browser yang sama untuk konfirmasi jadwal / reschedule /
// heregistrasi.
export const CONFIRMATION_TOKEN_KEY = 'pmb_confirmation_token';

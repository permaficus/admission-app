/**
 * api.js — helper untuk fetch ke Laravel API backend
 * Base URL diambil dari env variable atau default ke localhost:8000
 */
const BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
const TOKEN_KEY = 'pmb_admin_token';

/** Ambil token yang tersimpan di sessionStorage */
export const getToken = () => sessionStorage.getItem(TOKEN_KEY);
/** Simpan token ke sessionStorage */
export const setToken = (token) => sessionStorage.setItem(TOKEN_KEY, token);
/** Hapus token dari sessionStorage */
export const removeToken = () => sessionStorage.removeItem(TOKEN_KEY);

/**
 * Fetch wrapper dengan format response standar dari backend PMB
 * Menyertakan Bearer token jika tersedia
 */
const apiFetch = async (path, options = {}) => {
  const token = getToken();
  const headers = { 'Content-Type': 'application/json', ...options.headers };
  if (token) headers['Authorization'] = `Bearer ${token}`;

  const res = await fetch(`${BASE_URL}${path}`, { ...options, headers });
  const json = await res.json();
  if (!res.ok || !json.success) {
    const err = new Error(json.message || 'Terjadi kesalahan pada server');
    err.errors = json.errors || null;
    err.status = res.status;
    throw err;
  }
  return json;
};

export const authApi = {
  /** POST /api/auth/login */
  login: (username, password) =>
    apiFetch('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ username, password }),
    }),

  /** POST /api/auth/logout */
  logout: () => apiFetch('/auth/logout', { method: 'POST' }),
};

export const pendaftarApi = {
  /** GET /api/pendaftar — ambil semua pendaftar (perlu token) */
  getAll: () => apiFetch('/pendaftar'),

  /** GET /api/pendaftar/{nomor} — cari berdasarkan nomor pendaftaran */
  getByNomor: (nomor) => apiFetch(`/pendaftar/${encodeURIComponent(nomor)}`),

  /** POST /api/pendaftar — daftar baru */
  store: (data) =>
    apiFetch('/pendaftar', {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  /** PATCH /api/pendaftar/{id}/status — ubah status (perlu token) */
  updateStatus: (id, status) =>
    apiFetch(`/pendaftar/${id}/status`, {
      method: 'PATCH',
      body: JSON.stringify({ status }),
    }),

  /** POST /api/pendaftar/{nomor}/heregistrasi — heregistrasi mahasiswa lolos */
  heregistrasi: (nomor) =>
    apiFetch(`/pendaftar/${encodeURIComponent(nomor)}/heregistrasi`, {
      method: 'POST',
    }),

  /** POST /api/pendaftar/{nomor}/konfirmasi-jadwal — peserta konfirmasi kehadiran tes */
  konfirmasiJadwal: (nomor) =>
    apiFetch(`/pendaftar/${encodeURIComponent(nomor)}/konfirmasi-jadwal`, {
      method: 'POST',
    }),

  /** POST /api/pendaftar/{nomor}/reschedule — peserta ajukan reschedule */
  reschedule: (nomor, alasan) =>
    apiFetch(`/pendaftar/${encodeURIComponent(nomor)}/reschedule`, {
      method: 'POST',
      body: JSON.stringify({ alasan }),
    }),
};

export const jadwalApi = {
  /** GET /api/jadwal-tes (admin) */
  getAll: (params = {}) => {
    const q = new URLSearchParams(params).toString();
    return apiFetch(`/jadwal-tes${q ? `?${q}` : ''}`);
  },

  /** POST /api/jadwal-tes (admin) */
  store: (data) =>
    apiFetch('/jadwal-tes', {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  /** GET /api/jadwal-tes/{id} (admin) */
  getById: (id) => apiFetch(`/jadwal-tes/${id}`),

  /** DELETE /api/jadwal-tes/{id} (admin) */
  destroy: (id) => apiFetch(`/jadwal-tes/${id}`, { method: 'DELETE' }),

  /** POST /api/jadwal-tes/{id}/assign-auto (admin) */
  assignAuto: (id, filter = {}) =>
    apiFetch(`/jadwal-tes/${id}/assign-auto`, {
      method: 'POST',
      body: JSON.stringify(filter),
    }),

  /** POST /api/jadwal-tes/{id}/peserta (admin) */
  assignManual: (id, data) =>
    apiFetch(`/jadwal-tes/${id}/peserta`, {
      method: 'POST',
      body: JSON.stringify(data),
    }),
};

export const pesertaTesApi = {
  /** GET /api/peserta-tes (admin) */
  getAll: (params = {}) => {
    const q = new URLSearchParams(params).toString();
    return apiFetch(`/peserta-tes${q ? `?${q}` : ''}`);
  },

  /** POST /api/peserta-tes/{id}/hadir (operator) */
  markHadir: (id, statusKehadiran) =>
    apiFetch(`/peserta-tes/${id}/hadir`, {
      method: 'POST',
      body: JSON.stringify({ status_kehadiran: statusKehadiran }),
    }),

  /** POST /api/peserta-tes/{id}/reschedule/approve (admin) */
  approveReschedule: (id, jadwalTesIdBaru) =>
    apiFetch(`/peserta-tes/${id}/reschedule/approve`, {
      method: 'POST',
      body: JSON.stringify({ jadwal_tes_id_baru: jadwalTesIdBaru }),
    }),

  /** POST /api/peserta-tes/{id}/reschedule/reject (admin) */
  rejectReschedule: (id) =>
    apiFetch(`/peserta-tes/${id}/reschedule/reject`, {
      method: 'POST',
    }),
};

export const statistikApi = {
  /** GET /api/statistik — statistik per prodi, jalur, status (perlu token) */
  get: () => apiFetch('/statistik'),
};

/** URL langsung untuk download CSV (buka di tab baru dengan token di header tidak bisa — gunakan query param workaround) */
export const getExportCsvUrl = () =>
  `${BASE_URL}/pendaftar/export/csv`;

import { useState } from 'react';
import Button from '../components/ui/Button';
import { pendaftarApi, pesertaTesApi } from '../utils/api';
import { JENIS_TES_LABELS, STATUS_KEHADIRAN_LABELS } from '../constants/jadwal';

/**
 * Operator — halaman check-in lapangan untuk panitia
 * Tidak butuh login Sanctum di MVP (gunakan public endpoint cek status); akses
 * dikontrol via URL terbatas. Iterasi lanjutan: tambahkan operator role + login.
 */
const Operator = () => {
  const [nomor, setNomor] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [pendaftar, setPendaftar] = useState(null);
  const [jadwal, setJadwal] = useState(null);
  const [markLoading, setMarkLoading] = useState(false);
  const [success, setSuccess] = useState('');

  const formatTanggal = (iso) => {
    if (!iso) return '-';
    return new Date(iso).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  };

  const formatJam = (jam) => (jam ? jam.substring(0, 5) : '-');

  const handleCek = async (e) => {
    e.preventDefault();
    if (!nomor.trim()) return;
    setLoading(true);
    setError('');
    setSuccess('');
    setPendaftar(null);
    setJadwal(null);
    try {
      const res = await pendaftarApi.getByNomor(nomor.trim().toUpperCase());
      setPendaftar(res.data);
      setJadwal(res.data.jadwal_tes || null);
      if (!res.data.jadwal_tes) {
        setError('Peserta belum memiliki jadwal tes');
      }
    } catch (err) {
      setError(
        err.message || 'Nomor pendaftaran tidak ditemukan (format harus PMB-YYYY-XXXX).'
      );
    } finally {
      setLoading(false);
    }
  };

  const handleMark = async (statusKehadiran) => {
    if (!jadwal) return;
    setMarkLoading(true);
    setError('');
    setSuccess('');
    try {
      const res = await pesertaTesApi.markHadir(jadwal.peserta_tes_id, statusKehadiran);
      setSuccess(`Status kehadiran berhasil diubah menjadi "${statusKehadiran}"`);
      // Refresh data
      setJadwal({
        ...jadwal,
        status_kehadiran: res.data.status_kehadiran,
      });
    } catch (err) {
      setError(err.message || 'Gagal menandai kehadiran. Pastikan Anda sudah login sebagai admin.');
    } finally {
      setMarkLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-50">
      <header className="bg-amber-500">
        <div className="max-w-3xl mx-auto px-4 py-4">
          <h1 className="font-bold text-white text-lg">Operator — Check-in Tes PMB</h1>
          <p className="text-xs text-amber-50">
            Halaman khusus panitia lapangan. Pastikan Anda sudah login admin di halaman /admin
            agar fitur tandai kehadiran berfungsi.
          </p>
        </div>
      </header>

      <main className="max-w-3xl mx-auto px-4 py-6 space-y-4">
        <div className="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
          <div>
            <h2 className="font-semibold text-slate-800 text-base mb-1">Cari Peserta</h2>
            <p className="text-xs text-slate-500">
              Masukkan nomor pendaftaran peserta untuk melihat jadwalnya hari ini.
            </p>
          </div>

          <form onSubmit={handleCek} className="flex gap-2">
            <input
              type="text"
              value={nomor}
              onChange={(e) => {
                setNomor(e.target.value);
                setError('');
                setSuccess('');
              }}
              placeholder="Contoh: PMB-2025-1234"
              className="flex-1 px-3 py-2.5 border border-slate-200 rounded-lg text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 min-h-[44px] text-sm font-mono uppercase"
            />
            <Button type="submit" variant="primary" disabled={loading}>
              {loading ? 'Mencari...' : 'Cek'}
            </Button>
          </form>

          {error && (
            <div className="text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
              {error}
            </div>
          )}
          {success && (
            <div className="text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
              ✓ {success}
            </div>
          )}

          {pendaftar && (
            <div className="border-t border-slate-200 pt-4 space-y-3">
              <div>
                <p className="text-xs text-slate-500 mb-0.5">Nama Peserta</p>
                <p className="font-semibold text-slate-800">{pendaftar.nama}</p>
              </div>
              <div className="grid grid-cols-2 gap-3 text-sm">
                <div>
                  <p className="text-xs text-slate-500 mb-0.5">Nomor Pendaftaran</p>
                  <p className="font-mono font-semibold text-blue-700">
                    {pendaftar.nomor_pendaftaran}
                  </p>
                </div>
                <div>
                  <p className="text-xs text-slate-500 mb-0.5">Program Studi</p>
                  <p className="font-medium text-slate-800">{pendaftar.prodi}</p>
                </div>
              </div>

              {jadwal && (
                <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg space-y-2">
                  <p className="font-semibold text-blue-800">{jadwal.nama}</p>
                  <div className="text-sm text-slate-700">
                    <p>{JENIS_TES_LABELS[jadwal.jenis] || jadwal.jenis}</p>
                    <p>
                      {formatTanggal(jadwal.tanggal)} · {formatJam(jadwal.jam_mulai)} –{' '}
                      {formatJam(jadwal.jam_selesai)} WIB
                    </p>
                    {jadwal.lokasi && (
                      <p className="text-xs text-slate-500 mt-1">📍 {jadwal.lokasi}</p>
                    )}
                  </div>
                  <div className="text-xs">
                    <span className="text-slate-500">Status saat ini: </span>
                    <span className="font-semibold text-slate-800">
                      {STATUS_KEHADIRAN_LABELS[jadwal.status_kehadiran] || jadwal.status_kehadiran}
                    </span>
                  </div>

                  <div className="grid grid-cols-2 gap-2 pt-2">
                    <Button
                      variant="success"
                      disabled={markLoading || jadwal.status_kehadiran === 'hadir'}
                      onClick={() => handleMark('hadir')}
                      className="text-xs py-2"
                    >
                      {markLoading ? '...' : '✓ Tandai Hadir'}
                    </Button>
                    <Button
                      variant="danger"
                      disabled={markLoading || jadwal.status_kehadiran === 'tidak_hadir'}
                      onClick={() => handleMark('tidak_hadir')}
                      className="text-xs py-2"
                    >
                      {markLoading ? '...' : '✗ Tidak Hadir'}
                    </Button>
                  </div>
                </div>
              )}
            </div>
          )}
        </div>

        <div className="text-center text-xs text-slate-400">
          Untuk akses halaman admin →{' '}
          <a href="/admin" className="underline text-blue-600">
            /admin
          </a>
        </div>
      </main>
    </div>
  );
};

export default Operator;

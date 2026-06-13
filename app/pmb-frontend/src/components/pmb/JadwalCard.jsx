import { useState } from 'react';
import Button from '../ui/Button';
import { pendaftarApi } from '../../utils/api';
import {
  JENIS_TES_LABELS,
  STATUS_KEHADIRAN_LABELS,
  STATUS_KEHADIRAN_COLORS,
  RESCHEDULE_STATUS_LABELS,
  RESCHEDULE_STATUS_COLORS,
} from '../../constants/jadwal';

/**
 * JadwalCard — tampilkan kartu jadwal_tes untuk peserta di halaman cek status
 * Props:
 *   jadwal: object { peserta_tes_id, jadwal_tes_id, nama, jenis, tanggal,
 *           jam_mulai, jam_selesai, lokasi, status_kehadiran, confirmed_at,
 *           nomor_meja, reschedule_status, reschedule_alasan, status_jadwal }
 *   nomorPendaftaran: string (untuk endpoint konfirmasi/reschedule)
 *   onRefresh: callback setelah aksi sukses
 */
const JadwalCard = ({ jadwal, nomorPendaftaran, onRefresh }) => {
  const [konfirmLoading, setKonfirmLoading] = useState(false);
  const [rescheduleOpen, setRescheduleOpen] = useState(false);
  const [alasan, setAlasan] = useState('');
  const [rescheduleLoading, setRescheduleLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const isConfirmed = jadwal.confirmed_at !== null;
  const isReschedulePending = jadwal.reschedule_status === 'requested';
  const isRescheduleRejected = jadwal.reschedule_status === 'rejected';
  const canKonfirmasi = !isConfirmed && jadwal.reschedule_status !== 'requested';
  const canReschedule = !isConfirmed && jadwal.reschedule_status === 'none';

  const formatTanggal = (iso) => {
    if (!iso) return '-';
    return new Date(iso).toLocaleDateString('id-ID', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  };

  const formatJam = (jam) => (jam ? jam.substring(0, 5) : '-');

  const handleKonfirmasi = async () => {
    setKonfirmLoading(true);
    setError('');
    setSuccess('');
    try {
      const res = await pendaftarApi.konfirmasiJadwal(nomorPendaftaran);
      setSuccess(res.message || 'Kehadiran berhasil dikonfirmasi');
      if (onRefresh) onRefresh();
    } catch (err) {
      setError(err.message || 'Gagal mengkonfirmasi kehadiran');
    } finally {
      setKonfirmLoading(false);
    }
  };

  const handleSubmitReschedule = async (e) => {
    e.preventDefault();
    if (alasan.trim().length < 10) {
      setError('Alasan minimal 10 karakter');
      return;
    }
    setRescheduleLoading(true);
    setError('');
    setSuccess('');
    try {
      const res = await pendaftarApi.reschedule(nomorPendaftaran, alasan.trim());
      setSuccess(res.message || 'Permintaan reschedule berhasil diajukan');
      setRescheduleOpen(false);
      setAlasan('');
      if (onRefresh) onRefresh();
    } catch (err) {
      setError(err.message || 'Gagal mengajukan reschedule');
    } finally {
      setRescheduleLoading(false);
    }
  };

  return (
    <div className="p-4 bg-blue-50 border border-blue-200 rounded-xl space-y-3">
      <div className="flex items-center justify-between">
        <h4 className="font-semibold text-blue-800">📅 Jadwal Tes Anda</h4>
        <span
          className={`text-xs px-2 py-0.5 rounded-full font-medium ${
            STATUS_KEHADIRAN_COLORS[jadwal.status_kehadiran] || 'bg-slate-100 text-slate-700'
          }`}
        >
          {STATUS_KEHADIRAN_LABELS[jadwal.status_kehadiran] || jadwal.status_kehadiran}
        </span>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
        <div>
          <p className="text-xs text-slate-500 mb-0.5">Nama Sesi</p>
          <p className="font-medium text-slate-800">{jadwal.nama}</p>
        </div>
        <div>
          <p className="text-xs text-slate-500 mb-0.5">Jenis</p>
          <p className="font-medium text-slate-800">
            {JENIS_TES_LABELS[jadwal.jenis] || jadwal.jenis}
          </p>
        </div>
        <div>
          <p className="text-xs text-slate-500 mb-0.5">Tanggal</p>
          <p className="font-medium text-slate-800">{formatTanggal(jadwal.tanggal)}</p>
        </div>
        <div>
          <p className="text-xs text-slate-500 mb-0.5">Waktu</p>
          <p className="font-medium text-slate-800">
            {formatJam(jadwal.jam_mulai)} – {formatJam(jadwal.jam_selesai)} WIB
          </p>
        </div>
        {isConfirmed && jadwal.lokasi && (
          <div className="md:col-span-2">
            <p className="text-xs text-slate-500 mb-0.5">📍 Lokasi</p>
            <p className="font-medium text-slate-800">{jadwal.lokasi}</p>
          </div>
        )}
        {isConfirmed && jadwal.nomor_meja && (
          <div>
            <p className="text-xs text-slate-500 mb-0.5">Nomor Meja</p>
            <p className="font-mono font-semibold text-blue-700">{jadwal.nomor_meja}</p>
          </div>
        )}
      </div>

      {!isConfirmed && (
        <p className="text-xs text-slate-500 italic">
          Detail lokasi dan nomor meja akan ditampilkan setelah Anda mengkonfirmasi kehadiran.
        </p>
      )}

      {isReschedulePending && (
        <div
          className={`text-xs px-3 py-2 rounded-lg font-medium ${RESCHEDULE_STATUS_COLORS.requested}`}
        >
          ⏳ {RESCHEDULE_STATUS_LABELS.requested} — panitia akan menghubungi Anda jika
          jadwal pengganti sudah tersedia.
        </div>
      )}

      {isRescheduleRejected && (
        <div className={`text-xs px-3 py-2 rounded-lg font-medium ${RESCHEDULE_STATUS_COLORS.rejected}`}>
          ❌ Permintaan reschedule ditolak. Silakan hubungi panitia untuk informasi lebih lanjut.
        </div>
      )}

      {error && (
        <div className="text-xs text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
          {error}
        </div>
      )}
      {success && (
        <div className="text-xs text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2">
          ✓ {success}
        </div>
      )}

      {(canKonfirmasi || canReschedule) && (
        <div className="flex flex-col sm:flex-row gap-2 pt-1">
          {canKonfirmasi && (
            <Button
              variant="success"
              disabled={konfirmLoading}
              onClick={handleKonfirmasi}
              className="flex-1 text-xs py-2"
            >
              {konfirmLoading ? 'Memproses...' : '✓ Konfirmasi Akan Hadir'}
            </Button>
          )}
          {canReschedule && !rescheduleOpen && (
            <Button
              variant="secondary"
              onClick={() => setRescheduleOpen(true)}
              className="flex-1 text-xs py-2"
            >
              Minta Reschedule
            </Button>
          )}
        </div>
      )}

      {rescheduleOpen && (
        <form
          onSubmit={handleSubmitReschedule}
          className="bg-white border border-slate-200 rounded-lg p-3 space-y-2"
        >
          <label className="block text-xs font-medium text-slate-700">
            Alasan reschedule (min. 10 karakter)
          </label>
          <textarea
            value={alasan}
            onChange={(e) => setAlasan(e.target.value)}
            placeholder="Contoh: Saya sakit dan tidak dapat hadir pada tanggal tersebut."
            rows={3}
            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
          />
          <div className="flex gap-2">
            <Button
              type="submit"
              variant="primary"
              disabled={rescheduleLoading || alasan.trim().length < 10}
              className="flex-1 text-xs py-2"
            >
              {rescheduleLoading ? 'Mengirim...' : 'Kirim Permintaan'}
            </Button>
            <Button
              type="button"
              variant="secondary"
              onClick={() => {
                setRescheduleOpen(false);
                setAlasan('');
                setError('');
              }}
              className="text-xs py-2"
            >
              Batal
            </Button>
          </div>
        </form>
      )}
    </div>
  );
};

export default JadwalCard;

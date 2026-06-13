import { useEffect, useState } from 'react';
import Button from '../ui/Button';
import { jadwalApi, pesertaTesApi } from '../../utils/api';
import { JENIS_TES_LIST, JENIS_TES_LABELS, STATUS_JADWAL_COLORS } from '../../constants/jadwal';
import { JALUR_LIST, PRODI_LIST } from '../../constants';

/**
 * JadwalAdmin — tab admin untuk mengelola jadwal_tes
 * Fitur MVP: list jadwal + buat baru + auto-assign + lihat permintaan reschedule
 */
const JadwalAdmin = () => {
  const [jadwalList, setJadwalList] = useState([]);
  const [pesertaReschedule, setPesertaReschedule] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({
    nama: '',
    jenis: 'tes_tulis',
    tanggal: '',
    jam_mulai: '09:00',
    jam_selesai: '11:00',
    lokasi: '',
    kuota: 20,
  });
  const [assigning, setAssigning] = useState(null);

  const fetchAll = async () => {
    setLoading(true);
    setError('');
    try {
      const [jadwalRes, pesertaRes] = await Promise.all([
        jadwalApi.getAll(),
        pesertaTesApi.getAll({ reschedule_status: 'requested' }),
      ]);
      setJadwalList(jadwalRes.data);
      setPesertaReschedule(pesertaRes.data);
    } catch (err) {
      setError(err.message || 'Gagal memuat data jadwal');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAll();
  }, []);

  const handleCreate = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    try {
      await jadwalApi.store(form);
      setSuccess('Jadwal berhasil dibuat');
      setShowForm(false);
      setForm({ ...form, nama: '', tanggal: '', lokasi: '' });
      fetchAll();
    } catch (err) {
      const detail =
        err.errors && Object.values(err.errors).flat().join(' ');
      setError(detail || err.message || 'Gagal membuat jadwal');
    }
  };

  const handleAutoAssign = async (jadwalId) => {
    setAssigning(jadwalId);
    setError('');
    setSuccess('');
    try {
      const res = await jadwalApi.assignAuto(jadwalId, {});
      setSuccess(`Berhasil meng-assign ${res.data.jumlah_assigned} peserta. Kuota sisa: ${res.data.kuota_sisa}.`);
      fetchAll();
    } catch (err) {
      setError(err.message || 'Gagal melakukan auto-assign');
    } finally {
      setAssigning(null);
    }
  };

  const handleApprove = async (pesertaId) => {
    // MVP: re-assign ke jadwal_tes_id pertama yang masih punya kuota
    const target = jadwalList.find((j) => j.kuota - j.kapasitas_terisi > 0);
    if (!target) {
      setError('Tidak ada jadwal lain dengan kuota tersisa untuk reschedule');
      return;
    }
    try {
      await pesertaTesApi.approveReschedule(pesertaId, target.id);
      setSuccess(`Reschedule disetujui. Peserta dipindahkan ke "${target.nama}".`);
      fetchAll();
    } catch (err) {
      setError(err.message || 'Gagal menyetujui reschedule');
    }
  };

  const handleReject = async (pesertaId) => {
    try {
      await pesertaTesApi.rejectReschedule(pesertaId);
      setSuccess('Permintaan reschedule ditolak');
      fetchAll();
    } catch (err) {
      setError(err.message || 'Gagal menolak reschedule');
    }
  };

  const formatTanggal = (iso) => {
    if (!iso) return '-';
    return new Date(iso).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    });
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="font-bold text-slate-800 text-lg">Jadwal Tes Seleksi</h2>
        <Button variant="primary" onClick={() => setShowForm(!showForm)}>
          {showForm ? 'Tutup Form' : '+ Buat Jadwal'}
        </Button>
      </div>

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

      {showForm && (
        <form
          onSubmit={handleCreate}
          className="bg-white border border-slate-200 rounded-xl p-4 grid grid-cols-1 md:grid-cols-2 gap-3"
        >
          <div className="md:col-span-2">
            <label className="block text-xs font-medium text-slate-700 mb-1">Nama Jadwal</label>
            <input
              type="text"
              required
              value={form.nama}
              onChange={(e) => setForm({ ...form, nama: e.target.value })}
              className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label className="block text-xs font-medium text-slate-700 mb-1">Jenis</label>
            <select
              value={form.jenis}
              onChange={(e) => setForm({ ...form, jenis: e.target.value })}
              className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              {JENIS_TES_LIST.map((j) => (
                <option key={j} value={j}>
                  {JENIS_TES_LABELS[j]}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="block text-xs font-medium text-slate-700 mb-1">Tanggal</label>
            <input
              type="date"
              required
              value={form.tanggal}
              onChange={(e) => setForm({ ...form, tanggal: e.target.value })}
              className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label className="block text-xs font-medium text-slate-700 mb-1">Jam Mulai</label>
            <input
              type="time"
              required
              value={form.jam_mulai}
              onChange={(e) => setForm({ ...form, jam_mulai: e.target.value })}
              className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label className="block text-xs font-medium text-slate-700 mb-1">Jam Selesai</label>
            <input
              type="time"
              required
              value={form.jam_selesai}
              onChange={(e) => setForm({ ...form, jam_selesai: e.target.value })}
              className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div className="md:col-span-2">
            <label className="block text-xs font-medium text-slate-700 mb-1">Lokasi</label>
            <input
              type="text"
              required
              value={form.lokasi}
              onChange={(e) => setForm({ ...form, lokasi: e.target.value })}
              className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label className="block text-xs font-medium text-slate-700 mb-1">Kuota</label>
            <input
              type="number"
              min="1"
              max="500"
              required
              value={form.kuota}
              onChange={(e) => setForm({ ...form, kuota: parseInt(e.target.value, 10) })}
              className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div className="md:col-span-2">
            <Button type="submit" variant="primary" className="w-full">
              Simpan Jadwal
            </Button>
          </div>
        </form>
      )}

      <div className="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-slate-50 text-xs font-semibold text-slate-600 uppercase">
            <tr>
              <th className="text-left px-3 py-2">Nama</th>
              <th className="text-left px-3 py-2">Jenis</th>
              <th className="text-left px-3 py-2">Tanggal</th>
              <th className="text-left px-3 py-2">Waktu</th>
              <th className="text-left px-3 py-2">Kuota</th>
              <th className="text-left px-3 py-2">Status</th>
              <th className="text-left px-3 py-2">Aksi</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {loading && (
              <tr>
                <td colSpan={7} className="px-3 py-4 text-center text-slate-500">
                  Memuat...
                </td>
              </tr>
            )}
            {!loading && jadwalList.length === 0 && (
              <tr>
                <td colSpan={7} className="px-3 py-4 text-center text-slate-500">
                  Belum ada jadwal tes. Klik "+ Buat Jadwal" untuk mulai.
                </td>
              </tr>
            )}
            {jadwalList.map((j) => (
              <tr key={j.id}>
                <td className="px-3 py-2 font-medium text-slate-800">{j.nama}</td>
                <td className="px-3 py-2 text-slate-700">
                  {JENIS_TES_LABELS[j.jenis] || j.jenis}
                </td>
                <td className="px-3 py-2 text-slate-700">{formatTanggal(j.tanggal)}</td>
                <td className="px-3 py-2 text-slate-700">
                  {(j.jam_mulai || '').substring(0, 5)} – {(j.jam_selesai || '').substring(0, 5)}
                </td>
                <td className="px-3 py-2 text-slate-700 font-mono">
                  {j.kapasitas_terisi}/{j.kuota}
                </td>
                <td className="px-3 py-2">
                  <span
                    className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                      STATUS_JADWAL_COLORS[j.status] || 'bg-slate-100 text-slate-700'
                    }`}
                  >
                    {j.status}
                  </span>
                </td>
                <td className="px-3 py-2">
                  <Button
                    variant="secondary"
                    onClick={() => handleAutoAssign(j.id)}
                    disabled={assigning === j.id || j.kuota - j.kapasitas_terisi <= 0}
                    className="text-xs py-1 px-2"
                  >
                    {assigning === j.id ? '...' : 'Auto-Assign'}
                  </Button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {pesertaReschedule.length > 0 && (
        <div className="bg-white border border-yellow-200 rounded-xl p-4 space-y-3">
          <h3 className="font-semibold text-slate-800">
            ⏳ Permintaan Reschedule ({pesertaReschedule.length})
          </h3>
          <div className="space-y-2">
            {pesertaReschedule.map((p) => (
              <div
                key={p.id}
                className="border border-slate-100 rounded-lg p-3 flex flex-col md:flex-row md:items-center gap-2"
              >
                <div className="flex-1 text-sm">
                  <p className="font-semibold text-slate-800">
                    {p.pendaftar?.nama} ({p.pendaftar?.nomor_pendaftaran})
                  </p>
                  <p className="text-xs text-slate-500">
                    {p.jadwal_tes?.nama} · {formatTanggal(p.jadwal_tes?.tanggal)}
                  </p>
                  <p className="text-xs text-slate-700 mt-1 italic">
                    "{p.reschedule_alasan}"
                  </p>
                </div>
                <div className="flex gap-2">
                  <Button
                    variant="success"
                    onClick={() => handleApprove(p.id)}
                    className="text-xs py-1 px-3"
                  >
                    Approve
                  </Button>
                  <Button
                    variant="danger"
                    onClick={() => handleReject(p.id)}
                    className="text-xs py-1 px-3"
                  >
                    Reject
                  </Button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

export default JadwalAdmin;

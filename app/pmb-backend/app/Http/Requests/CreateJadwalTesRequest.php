<?php

namespace App\Http\Requests;

use App\Models\JadwalTes;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validasi pembuatan jadwal_tes baru oleh admin.
 */
class CreateJadwalTesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'min:3', 'max:150'],
            'jenis' => ['required', 'string', 'in:' . implode(',', JadwalTes::JENIS_LIST)],
            'tanggal' => ['required', 'date', 'after_or_equal:today'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'lokasi' => ['required', 'string', 'min:3', 'max:255'],
            'kuota' => ['required', 'integer', 'min:1', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama jadwal wajib diisi.',
            'nama.min' => 'Nama jadwal minimal 3 karakter.',
            'jenis.required' => 'Jenis tes wajib dipilih.',
            'jenis.in' => 'Jenis tes harus tes_tulis atau wawancara.',
            'tanggal.required' => 'Tanggal jadwal wajib diisi.',
            'tanggal.after_or_equal' => 'Tanggal jadwal tidak boleh sebelum hari ini.',
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'jam_mulai.date_format' => 'Jam mulai harus format HH:MM.',
            'jam_selesai.required' => 'Jam selesai wajib diisi.',
            'jam_selesai.date_format' => 'Jam selesai harus format HH:MM.',
            'jam_selesai.after' => 'Jam selesai harus setelah jam mulai.',
            'lokasi.required' => 'Lokasi wajib diisi.',
            'kuota.required' => 'Kuota wajib diisi.',
            'kuota.min' => 'Kuota minimal 1 peserta.',
            'kuota.max' => 'Kuota maksimal 500 peserta.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors' => $validator->errors(),
        ], 422));
    }
}

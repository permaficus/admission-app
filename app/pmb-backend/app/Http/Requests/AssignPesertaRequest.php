<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validasi assign peserta manual ke jadwal_tes (admin).
 */
class AssignPesertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nomor_pendaftaran' => ['required', 'string', 'regex:/^PMB-[0-9]{4}-[0-9]{4}$/'],
            'nomor_meja' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'nomor_pendaftaran.required' => 'Nomor pendaftaran wajib diisi.',
            'nomor_pendaftaran.regex' => 'Format nomor pendaftaran tidak valid (harus PMB-YYYY-XXXX).',
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

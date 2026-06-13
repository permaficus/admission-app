<?php

namespace App\Http\Requests;

use App\Models\PesertaTes;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validasi penandaan kehadiran peserta (operator).
 */
class MarkHadirRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status_kehadiran' => [
                'required',
                'string',
                'in:' . PesertaTes::STATUS_HADIR . ',' . PesertaTes::STATUS_TIDAK_HADIR,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status_kehadiran.required' => 'Status kehadiran wajib diisi.',
            'status_kehadiran.in' => 'Status kehadiran harus hadir atau tidak_hadir.',
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

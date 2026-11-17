<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PengajuanVerifikasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['verified', 'rejected'])],
            'catatan' => [
                Rule::requiredIf($this->input('status') === 'rejected'),
                'nullable',
                'string',
                'min:5',
                'max:1000',
            ],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'status' => 'status verifikasi',
            'catatan' => 'catatan',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'status.required' => 'Status verifikasi wajib diisi.',
            'status.in' => 'Status verifikasi harus verified atau rejected.',
            'catatan.required' => 'Catatan wajib diisi jika pengajuan ditolak.',
            'catatan.min' => 'Catatan minimal 5 karakter.',
            'catatan.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }
}

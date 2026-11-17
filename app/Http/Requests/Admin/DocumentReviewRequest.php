<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentReviewRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status_pemeriksaan' => ['required', Rule::in(['disetujui', 'ditolak'])],
            'catatan_admin' => [
                Rule::requiredIf($this->input('status_pemeriksaan') === 'ditolak'),
                'nullable',
                'string',
                'min:5',
                'max:1000',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'catatan_admin.required' => 'Catatan wajib diisi jika dokumen ditolak.',
            'catatan_admin.min' => 'Catatan minimal 5 karakter.',
            'status_pemeriksaan.in' => 'Status pemeriksaan tidak valid.',
        ];
    }
}

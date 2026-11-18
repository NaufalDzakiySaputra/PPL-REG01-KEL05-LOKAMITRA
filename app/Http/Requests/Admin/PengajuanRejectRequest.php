<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PengajuanRejectRequest extends FormRequest
{
    /**
     * Spesifikasi memakai "catatan", sedangkan aturan lama memakai "alasan".
     * Keduanya diterima dan dinormalisasi sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('alasan') && $this->filled('catatan')) {
            $this->merge(['alasan' => $this->input('catatan')]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'alasan' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'alasan.required' => 'Alasan penolakan wajib diisi.',
            'alasan.min' => 'Alasan penolakan minimal 5 karakter.',
            'alasan.max' => 'Alasan penolakan maksimal 1000 karakter.',
        ];
    }
}

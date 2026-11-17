<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class DokumenUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'jenis_dokumen' => ['required', 'string', 'max:255'],
            'file_dokumen' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'jenis_dokumen' => 'jenis dokumen',
            'file_dokumen' => 'file dokumen',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'string' => ':Attribute harus berupa teks.',
            'max' => ':Attribute maksimal :max.',
            'file' => ':Attribute harus berupa berkas.',
            'mimes' => ':Attribute harus berformat PDF, JPG, JPEG, atau PNG.',
            'file_dokumen.max' => 'Ukuran file dokumen maksimal 5 MB.',
        ];
    }
}

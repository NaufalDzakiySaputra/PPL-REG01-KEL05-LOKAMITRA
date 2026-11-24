<?php

namespace App\Http\Requests\Forum;

use Illuminate\Foundation\Http\FormRequest;

class StoreLampiranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'pesan_id' => ['required', 'integer', 'exists:pesan_forum,pesan_forum_id'],
            'file_lampiran' => [
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
            'pesan_id' => 'pesan yang dilampiri',
            'file_lampiran' => 'file lampiran',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'integer' => ':Attribute harus berupa angka.',
            'exists' => ':Attribute tidak ditemukan.',
            'file' => ':Attribute harus berupa berkas.',
            'mimes' => ':Attribute harus berformat PDF, JPG, JPEG, atau PNG.',
            'max' => ':Attribute tidak boleh lebih dari 5 MB.',
        ];
    }
}

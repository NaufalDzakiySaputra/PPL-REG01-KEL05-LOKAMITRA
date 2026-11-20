<?php

namespace App\Http\Requests\Eo;

use App\Models\DokumenEvent;
use Illuminate\Validation\Rule;

// Memperbarui dokumen/layout yang sudah diunggah (PBI-036).
// Berkas baru bersifat opsional: hanya divalidasi bila EO menggantinya.
class UpdateDokumenEventRequest extends InformasiPublikRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_dokumen' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'string', Rule::in(DokumenEvent::DAFTAR_JENIS)],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute tidak boleh lebih dari :max karakter.',
            'in' => 'Pilihan :attribute tidak valid.',
            'file' => ':attribute harus berupa berkas.',
            'mimes' => ':attribute harus berformat PDF, JPG, JPEG, atau PNG.',
            'file.max' => 'Ukuran berkas maksimal 5 MB.',
        ];
    }
}

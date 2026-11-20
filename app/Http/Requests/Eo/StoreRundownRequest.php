<?php

namespace App\Http\Requests\Eo;

class StoreRundownRequest extends InformasiPublikRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'judul_aktivitas' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'waktu_mulai' => ['required', 'date'],
            'waktu_selesai' => ['nullable', 'date', 'after_or_equal:waktu_mulai'],
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
            'date' => ':attribute harus berupa tanggal dan waktu yang valid.',
            'waktu_selesai.after_or_equal' => 'Waktu selesai tidak boleh sebelum waktu mulai.',
        ];
    }
}

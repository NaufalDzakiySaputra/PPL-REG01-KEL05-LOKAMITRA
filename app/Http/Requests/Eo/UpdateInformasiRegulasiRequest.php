<?php

namespace App\Http\Requests\Eo;

// Memperbarui informasi & regulasi event langsung dari halaman detail EO (PBI-036).
// Dipisahkan dari UpdateEventRequest agar tidak memaksa EO mengirim ulang
// seluruh data event (termasuk tanggal mulai) hanya untuk mengubah regulasi.
class UpdateInformasiRegulasiRequest extends InformasiPublikRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'informasi_regulasi' => ['nullable', 'string', 'max:50000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'max' => ':attribute tidak boleh lebih dari :max karakter.',
        ];
    }
}

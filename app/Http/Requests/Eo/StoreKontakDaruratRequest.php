<?php

namespace App\Http\Requests\Eo;

use App\Models\KontakDaruratEvent;
use Illuminate\Contracts\Validation\Validator;

class StoreKontakDaruratRequest extends InformasiPublikRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            'nomor_telepon' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{6,20}$/'],
        ];
    }

    // Batas dua kontak ditegakkan di sisi tulis, bukan hanya saat menampilkan.
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $event = $this->event();

            if ($event === null) {
                return;
            }

            $jumlah = $event->kontakDarurat()->count();

            if ($jumlah >= KontakDaruratEvent::MAKSIMAL_PER_EVENT) {
                $validator->errors()->add(
                    'nama',
                    'Setiap event hanya boleh memiliki maksimal '
                        .KontakDaruratEvent::MAKSIMAL_PER_EVENT.' kontak darurat.',
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute tidak boleh lebih dari :max karakter.',
            'regex' => ':attribute harus berupa nomor telepon yang valid.',
        ];
    }
}

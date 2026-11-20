<?php

namespace App\Http\Requests\Eo;

use Illuminate\Contracts\Validation\Validator;

// Saat mengubah kontak yang sudah ada, batas dua kontak tidak dihitung ulang.
class UpdateKontakDaruratRequest extends StoreKontakDaruratRequest
{
    public function withValidator(Validator $validator): void
    {
        // Tidak ada pengecekan kuota: baris yang diubah sudah dihitung.
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatAktivitas extends Model
{
    protected $table = 'riwayat_aktivitas';

    protected $primaryKey = 'riwayat_id';

    protected $fillable = [
        'akun_id',
        'jenis_aktivitas',
        'deskripsi',
        'waktu_aktivitas',
    ];

    protected function casts(): array
    {
        return [
            'waktu_aktivitas' => 'datetime',
        ];
    }

    public function akun(): BelongsTo
    {
        return $this->belongsTo(User::class, 'akun_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';

    protected $primaryKey = 'notifikasi_id';

    protected $fillable = [
        'akun_id',
        'isi_notifikasi',
        'waktu_dibuat',
        'status_baca',
    ];

    protected function casts(): array
    {
        return [
            'waktu_dibuat' => 'datetime',
        ];
    }

    public function akun(): BelongsTo
    {
        return $this->belongsTo(User::class, 'akun_id');
    }
}

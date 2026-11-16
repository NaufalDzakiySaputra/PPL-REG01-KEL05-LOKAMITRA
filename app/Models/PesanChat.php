<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesanChat extends Model
{
    protected $table = 'pesan_chat';

    protected $primaryKey = 'pesan_chat_id';

    protected $fillable = [
        'grup_chat_id',
        'pengirim_akun_id',
        'isi_pesan',
        'waktu_kirim',
    ];

    protected function casts(): array
    {
        return [
            'waktu_kirim' => 'datetime',
        ];
    }

    public function grupChat(): BelongsTo
    {
        return $this->belongsTo(GrupChat::class, 'grup_chat_id', 'grup_chat_id');
    }

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengirim_akun_id');
    }
}

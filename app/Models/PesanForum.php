<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PesanForum extends Model
{
    protected $table = 'pesan_forum';

    protected $primaryKey = 'pesan_forum_id';

    protected $fillable = [
        'forum_id',
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

    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class, 'forum_id', 'forum_id');
    }

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengirim_akun_id');
    }

    public function lampiran(): HasMany
    {
        return $this->hasMany(LampiranForum::class, 'pesan_forum_id', 'pesan_forum_id');
    }
}

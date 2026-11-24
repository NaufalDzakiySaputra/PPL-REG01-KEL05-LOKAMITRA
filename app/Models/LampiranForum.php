<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LampiranForum extends Model
{
    protected $table = 'lampiran_forum';

    protected $primaryKey = 'lampiran_id';

    public $timestamps = false;

    protected $fillable = [
        'pesan_forum_id',
        'pengunggah_akun_id',
        'nama_file',
        'lokasi_file',
        'format_file',
        'ukuran_file',
        'waktu_upload',
    ];

    protected function casts(): array
    {
        return [
            'waktu_upload' => 'datetime',
        ];
    }

    public function pesan(): BelongsTo
    {
        return $this->belongsTo(PesanForum::class, 'pesan_forum_id', 'pesan_forum_id');
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengunggah_akun_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tugas extends Model
{
    // Status tugas.
    public const STATUS_BELUM = 'belum_mulai';

    public const STATUS_BERJALAN = 'berjalan';

    public const STATUS_SELESAI = 'selesai';

    protected $table = 'tugas';

    protected $primaryKey = 'tugas_id';

    protected $fillable = [
        'event_id',
        'penerima_akun_id',
        'judul',
        'deskripsi',
        'tanggal_deadline',
        'status_tugas',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_deadline' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penerima_akun_id');
    }
}

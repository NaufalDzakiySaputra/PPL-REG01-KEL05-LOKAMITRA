<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeanggotaanEvent extends Model
{
    protected $table = 'keanggotaan_event';

    protected $primaryKey = 'keanggotaan_id';

    protected $fillable = [
        'event_id',
        'akun_id',
        'tanggal_bergabung',
        'status_keanggotaan',
        'pesan_pengajuan',
        'tanggal_respons',
    ];

    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DITERIMA = 'diterima';

    public const STATUS_DITOLAK = 'ditolak';

    protected function casts(): array
    {
        return [
            'tanggal_bergabung' => 'datetime',
            'tanggal_respons' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }

    public function akun(): BelongsTo
    {
        return $this->belongsTo(User::class, 'akun_id');
    }
}

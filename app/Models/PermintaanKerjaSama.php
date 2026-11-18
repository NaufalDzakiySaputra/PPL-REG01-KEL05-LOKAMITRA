<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermintaanKerjaSama extends Model
{
    protected $table = 'permintaan_kerja_sama';

    protected $fillable = [
        'event_id',
        'stakeholder_akun_id',
        'pesan',
        'status',
    ];

    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DITERIMA = 'diterima';

    public const STATUS_DITOLAK = 'ditolak';

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }

    public function stakeholder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stakeholder_akun_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengumuman extends Model
{
    protected $table = 'pengumuman';

    protected $primaryKey = 'pengumuman_id';

    protected $fillable = [
        'event_id',
        'judul',
        'isi_pengumuman',
        'tanggal_dibuat',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_dibuat' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }
}

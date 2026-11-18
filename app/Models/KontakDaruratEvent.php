<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Kontak darurat yang bisa dihubungi peserta saat event berlangsung.
// Maksimal dua kontak untuk setiap event.
class KontakDaruratEvent extends Model
{
    public const MAKSIMAL_PER_EVENT = 2;

    protected $table = 'kontak_darurat_event';

    protected $primaryKey = 'kontak_darurat_event_id';

    protected $fillable = [
        'event_id',
        'nama',
        'nomor_telepon',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }
}

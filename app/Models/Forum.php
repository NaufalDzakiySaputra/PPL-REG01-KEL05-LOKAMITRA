<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Forum extends Model
{
    protected $table = 'forum';

    protected $primaryKey = 'forum_id';

    protected $fillable = [
        'event_id',
        'judul',
        'deskripsi',
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

    public function pesan(): HasMany
    {
        return $this->hasMany(PesanForum::class, 'forum_id', 'forum_id');
    }
}

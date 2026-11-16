<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrupChat extends Model
{
    protected $table = 'grup_chat';

    protected $primaryKey = 'grup_chat_id';

    protected $fillable = [
        'event_id',
        'nama_grup',
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
        return $this->hasMany(PesanChat::class, 'grup_chat_id', 'grup_chat_id');
    }
}

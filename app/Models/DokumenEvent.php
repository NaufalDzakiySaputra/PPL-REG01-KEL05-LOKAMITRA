<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenEvent extends Model
{
    public const JENIS_LAYOUT = 'layout';

    public const JENIS_DOKUMEN = 'dokumen';

    public const DAFTAR_JENIS = [self::JENIS_LAYOUT, self::JENIS_DOKUMEN];

    protected $table = 'dokumen_event';

    protected $primaryKey = 'dokumen_event_id';

    protected $fillable = [
        'event_id',
        'pengunggah_akun_id',
        'nama_dokumen',
        'jenis',
        'lokasi_file',
        'tanggal_upload',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_upload' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengunggah_akun_id');
    }
}

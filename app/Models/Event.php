<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $table = 'event';

    protected $primaryKey = 'event_id';

    protected $fillable = [
        'eo_id',
        'nama_event',
        'deskripsi',
        'lokasi',
        'tanggal_mulai',
        'tanggal_selesai',
        'status_event',
        'kategori_event',
        'informasi_regulasi',
        'poster',
        'diverifikasi_oleh',
        'tanggal_diverifikasi',
        'tanggal_ditutup',
        'tanggal_diarsipkan',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_MENUNGGU_VERIFIKASI = 'menunggu_verifikasi';

    public const STATUS_DIPUBLIKASIKAN = 'dipublikasikan';

    public const STATUS_DITUTUP = 'ditutup';

    public const STATUS_DIARSIPKAN = 'diarsipkan';

    // Event yang sudah dipublikasikan dan belum lewat tanggal selesainya.
    public function scopeTersediaUmum(Builder $query): Builder
    {
        return $query
            ->where('status_event', self::STATUS_DIPUBLIKASIKAN)
            ->whereDate('tanggal_selesai', '>=', today());
    }

    // Event dipublikasikan yang sedang berlangsung hari ini.
    public function scopeBerjalan(Builder $query): Builder
    {
        return $query
            ->where('status_event', self::STATUS_DIPUBLIKASIKAN)
            ->whereDate('tanggal_mulai', '<=', today())
            ->whereDate('tanggal_selesai', '>=', today());
    }

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'tanggal_diverifikasi' => 'datetime',
            'tanggal_ditutup' => 'datetime',
            'tanggal_diarsipkan' => 'datetime',
        ];
    }

    public function eo(): BelongsTo
    {
        return $this->belongsTo(Eo::class, 'eo_id', 'eo_id');
    }

    public function adminVerifikator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'diverifikasi_oleh', 'admin_id');
    }

    public function keanggotaan(): HasMany
    {
        return $this->hasMany(KeanggotaanEvent::class, 'event_id', 'event_id');
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(Timeline::class, 'event_id', 'event_id');
    }

    public function tugas(): HasMany
    {
        return $this->hasMany(Tugas::class, 'event_id', 'event_id');
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(DokumenEvent::class, 'event_id', 'event_id');
    }

    public function forum(): HasMany
    {
        return $this->hasMany(Forum::class, 'event_id', 'event_id');
    }

    public function grupChat(): HasMany
    {
        return $this->hasMany(GrupChat::class, 'event_id', 'event_id');
    }

    public function pengumuman(): HasMany
    {
        return $this->hasMany(Pengumuman::class, 'event_id', 'event_id');
    }

    public function dapatDiubah(): bool
    {
        return ! in_array($this->status_event, [
            self::STATUS_DITUTUP,
            self::STATUS_DIARSIPKAN,
        ], true);
    }

    public function dapatDitutup(): bool
    {
        return $this->status_event === self::STATUS_DIPUBLIKASIKAN;
    }

    public function scopeSiapDiarsipkan(Builder $query): Builder
    {
        return $query->where('status_event', self::STATUS_DITUTUP);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Eo extends Model
{
    // buat nama tabel
    protected $table = 'eo';

    // buat primary key
    protected $primaryKey = 'eo_id';

    // kolom yang bisa diisi
    protected $fillable = [
        'akun_id',
        'admin_id',
        'nama_eo_organisasi',
        'website_media_sosial',
        'alamat_kantor',
        'jenis_event_yang_ditangani',
        'status_verifikasi',
    ];

    // status verifikasi EO
    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_DITOLAK = 'ditolak';

    // relasi ke model User
    public function akun(): BelongsTo
    {
        return $this->belongsTo(User::class, 'akun_id');
    }

    // Admin yang memverifikasi profil EO ini.
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }

    // Event yang dibuat oleh EO ini.
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'eo_id', 'eo_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    // nama tabel
    protected $table = 'media';

    // primary key
    protected $primaryKey = 'media_id';

    // kolom yang bisa diisi
    protected $fillable = [
        'akun_id',
        'admin_id',
        'nama_media',
        'nama_pemimpin_redaksi',
        'wilayah_liputan',
        'jenis_media',
        'status_verifikasi',
    ];

    // status verifikasi
    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_DITOLAK = 'ditolak';

    // relasi ke akun pengguna
    public function akun(): BelongsTo
    {
        return $this->belongsTo(User::class, 'akun_id');
    }

    // relasi ke admin yang memverifikasi
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }
}

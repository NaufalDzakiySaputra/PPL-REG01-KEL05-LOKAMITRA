<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KomunitasLokal extends Model
{
    // nama tabel
    protected $table = 'komunitas_lokal';

    // primary key
    protected $primaryKey = 'komunitas_id';

    // kolom yang bisa diisi
    protected $fillable = [
        'akun_id',
        'admin_id',
        'nama_komunitas',
        'perkiraan_jumlah_anggota',
        'wilayah_kegiatan',
        'bidang_kegiatan',
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

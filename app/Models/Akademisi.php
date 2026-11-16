<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Akademisi extends Model
{
    // nama tabel
    protected $table = 'akademisi';

    // primary key
    protected $primaryKey = 'akademisi_id';

    // kolom yang bisa diisi
    protected $fillable = [
        'akun_id',
        'admin_id',
        'nama_institusi',
        'fakultas_unit_jurusan',
        'bidang_keahlian',
        'tautan_profil_riset',
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

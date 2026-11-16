<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorUmkm extends Model
{
    // nama tabel
    protected $table = 'vendor_umkm';

    // primary key
    protected $primaryKey = 'vendor_id';

    // kolom yang bisa diisi
    protected $fillable = [
        'akun_id',
        'admin_id',
        'nama_usaha',
        'alamat_usaha',
        'nama_pemilik',
        'bidang_usaha',
        'status_verifikasi',
    ];

    // status verifikasi Vendor/UMKM
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

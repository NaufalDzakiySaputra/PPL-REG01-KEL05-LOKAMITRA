<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenPersyaratan extends Model
{
    protected $table = 'dokumen_persyaratan';

    protected $primaryKey = 'dokumen_id';

    protected $fillable = [
        'akun_id',
        'admin_id',
        'jenis_dokumen',
        'nama_file',
        'format_file',
        'lokasi_file',
        'sifat_dokumen',
        'tanggal_upload',
        'status_pemeriksaan',
        'catatan_admin',
    ];

    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_DITOLAK = 'ditolak';

    protected function casts(): array
    {
        return [
            'tanggal_upload' => 'datetime',
        ];
    }

    public function akun(): BelongsTo
    {
        return $this->belongsTo(User::class, 'akun_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }
}

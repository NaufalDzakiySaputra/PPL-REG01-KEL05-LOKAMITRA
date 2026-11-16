<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Laporan extends Model
{
    protected $table = 'laporan';

    protected $primaryKey = 'laporan_id';

    protected $fillable = [
        'admin_id',
        'jenis_laporan',
        'isi_laporan',
        'tanggal_dibuat',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_dibuat' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }
}

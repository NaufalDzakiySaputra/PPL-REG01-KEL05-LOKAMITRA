<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admin extends Model
{
    // nama tabel
    protected $table = 'admin';

    // primary key
    protected $primaryKey = 'admin_id';

    // kolom yang bisa diisi
    protected $fillable = [
        'akun_id',
    ];

    // relasi ke akun pengguna
    public function akun(): BelongsTo
    {
        return $this->belongsTo(User::class, 'akun_id');
    }

    // Dokumen persyaratan yang pernah diperiksa oleh Admin ini.
    public function dokumenDiperiksa(): HasMany
    {
        return $this->hasMany(DokumenPersyaratan::class, 'admin_id', 'admin_id');
    }

    public function eoDiverifikasi(): HasMany
    {
        return $this->hasMany(Eo::class, 'admin_id', 'admin_id');
    }

    public function vendorDiverifikasi(): HasMany
    {
        return $this->hasMany(VendorUmkm::class, 'admin_id', 'admin_id');
    }

    public function pemdaDiverifikasi(): HasMany
    {
        return $this->hasMany(PemerintahDaerah::class, 'admin_id', 'admin_id');
    }

    public function komunitasDiverifikasi(): HasMany
    {
        return $this->hasMany(KomunitasLokal::class, 'admin_id', 'admin_id');
    }

    public function akademisiDiverifikasi(): HasMany
    {
        return $this->hasMany(Akademisi::class, 'admin_id', 'admin_id');
    }

    public function mediaDiverifikasi(): HasMany
    {
        return $this->hasMany(Media::class, 'admin_id', 'admin_id');
    }

    // Event yang diverifikasi oleh Admin ini.
    public function eventsDiverifikasi(): HasMany
    {
        return $this->hasMany(Event::class, 'diverifikasi_oleh', 'admin_id');
    }

    public function laporan(): HasMany
    {
        return $this->hasMany(Laporan::class, 'admin_id', 'admin_id');
    }
}

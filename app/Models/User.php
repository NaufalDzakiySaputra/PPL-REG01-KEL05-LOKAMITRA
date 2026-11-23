<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // Kolom yang boleh diisi melalui model.
    protected $fillable = [
        'name',
        'email',
        'nomor_telepon',
        'role',
        'status_akun',
        'password',
    ];

    // Kolom yang tidak ditampilkan.
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    // Daftar role Lokamitra.
    public const ROLE_ADMIN = 'admin';

    public const ROLE_EO = 'eo';

    public const ROLE_VENDOR = 'vendor';

    public const ROLE_PEMDA = 'pemda';

    public const ROLE_KOMUNITAS = 'komunitas';

    public const ROLE_AKADEMISI = 'akademisi';

    public const ROLE_MEDIA = 'media';

    public const ROLE_PENGUNJUNG = 'pengunjung';

    // Status akun.
    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_DITOLAK = 'ditolak';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isEo(): bool
    {
        return $this->role === self::ROLE_EO;
    }

    public function isActive(): bool
    {
        return $this->status_akun === self::STATUS_AKTIF;
    }

    // Menentukan nama route dashboard berdasarkan role.
    public function dashboardRouteName(): ?string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'admin.dashboard',
            self::ROLE_EO => 'eo.dashboard',
            self::ROLE_VENDOR => 'vendor.dashboard',
            self::ROLE_PEMDA => 'pemda.dashboard',
            self::ROLE_KOMUNITAS => 'komunitas.dashboard',
            self::ROLE_AKADEMISI => 'akademisi.dashboard',
            self::ROLE_MEDIA => 'media.dashboard',
            self::ROLE_PENGUNJUNG => 'pengunjung.dashboard',
            default => null,
        };
    }

    // Relasi ke tabel profil per role.
    public function admin(): HasOne
    {
        return $this->hasOne(Admin::class, 'akun_id');
    }

    public function eo(): HasOne
    {
        return $this->hasOne(Eo::class, 'akun_id');
    }

    public function vendor(): HasOne
    {
        return $this->hasOne(VendorUmkm::class, 'akun_id');
    }

    public function pemerintahDaerah(): HasOne
    {
        return $this->hasOne(PemerintahDaerah::class, 'akun_id');
    }

    public function komunitasLokal(): HasOne
    {
        return $this->hasOne(KomunitasLokal::class, 'akun_id');
    }

    public function akademisi(): HasOne
    {
        return $this->hasOne(Akademisi::class, 'akun_id');
    }

    public function media(): HasOne
    {
        return $this->hasOne(Media::class, 'akun_id');
    }

    // Dokumen persyaratan yang diunggah oleh akun.
    public function dokumenPersyaratan(): HasMany
    {
        return $this->hasMany(DokumenPersyaratan::class, 'akun_id');
    }

    // Riwayat aktivitas yang dicatat oleh akun ini.
    public function riwayatAktivitas(): HasMany
    {
        return $this->hasMany(RiwayatAktivitas::class, 'akun_id');
    }

    // Notifikasi yang diterima akun.
    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'akun_id');
    }

    // Relasi ke aktivitas operasional yang dibuat atau diterima akun ini.
    public function keanggotaanEvent(): HasMany
    {
        return $this->hasMany(KeanggotaanEvent::class, 'akun_id');
    }

    public function tugasDiterima(): HasMany
    {
        return $this->hasMany(Tugas::class, 'penerima_akun_id');
    }

    public function dokumenEventDiunggah(): HasMany
    {
        return $this->hasMany(DokumenEvent::class, 'pengunggah_akun_id');
    }

    public function forumDibuat(): HasMany
    {
        return $this->hasMany(Forum::class, 'pembuat_akun_id');
    }

    public function pesanForum(): HasMany
    {
        return $this->hasMany(PesanForum::class, 'pengirim_akun_id');
    }

    public function pesanChat(): HasMany
    {
        return $this->hasMany(PesanChat::class, 'pengirim_akun_id');
    }
}

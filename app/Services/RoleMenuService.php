<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Menu sidebar per role (sesuai Figma). Dipakai bersama oleh seluruh halaman
 * lewat HandleInertiaRequests ('menu').
 *
 * Item menu otomatis AKTIF begitu route-nya ada. Selama route modul belum
 * dibuat (Tahap 7), 'href' bernilai null dan item tampil nonaktif.
 * Format 'route': nama route tanpa awalan role, mis. 'events.index' -> 'eo.events.index'.
 */
class RoleMenuService
{
    // Menu bersama untuk EO dan kelima mitra.
    private const MODUL = [
        'events.index' => ['Event', 'event'],
        'stakeholder.index' => ['Stakeholder', 'stakeholder'],
        'forum.index' => ['Forum Diskusi', 'forum'],
        'chat.index' => ['Group Chat', 'chat'],
        'dokumen.index' => ['Dokumen', 'dokumen'],
        'tugas.index' => ['Tugas', 'tugas'],
        'timeline.index' => ['Timeline & Kalender', 'timeline'],
        'pengumuman.index' => ['Papan Informasi', 'pengumuman'],
        'laporan.index' => ['Laporan', 'laporan'],
        'riwayat.index' => ['Riwayat Aktivitas', 'riwayat'],
    ];

    // Modul yang tampil per kelompok role (urutan = urutan di sidebar).
    private const MODUL_EO = [
        'events.index', 'stakeholder.index', 'forum.index', 'chat.index', 'dokumen.index',
        'tugas.index', 'timeline.index', 'pengumuman.index', 'laporan.index', 'riwayat.index',
    ];

    private const MODUL_MITRA = [
        'events.index', 'forum.index', 'chat.index', 'dokumen.index',
        'tugas.index', 'timeline.index', 'pengumuman.index', 'riwayat.index',
    ];

    public function untuk(User $user): array
    {
        return match ($user->role) {
            User::ROLE_ADMIN => $this->admin(),
            User::ROLE_EO => $this->peran($user, self::MODUL_EO, true),
            User::ROLE_VENDOR, User::ROLE_PEMDA, User::ROLE_KOMUNITAS,
            User::ROLE_AKADEMISI, User::ROLE_MEDIA => $this->peran($user, self::MODUL_MITRA, true),
            default => ['panel' => null, 'tema' => 'terang', 'peranLabel' => '', 'items' => []],
        };
    }

    private function admin(): array
    {
        return [
            'panel' => 'PANEL ADMIN',
            'tema' => 'terang',
            'peranLabel' => 'Admin',
            'items' => [
                $this->item('Dashboard Admin', 'dashboard', 'admin.dashboard'),
                $this->item('Verifikasi dan Arsip', 'verifikasi', 'admin.pengajuan.index'),
                $this->item('Daftar Event', 'event', 'admin.events.index'),
                $this->item('Kelola Stakeholder', 'stakeholder', 'admin.stakeholder.index'),
                $this->item('Laporan Aktivitas', 'laporan', 'admin.laporan.index'),
                $this->item('Riwayat Aktivitas', 'riwayat', 'admin.riwayat.index'),
            ],
        ];
    }

    private function peran(User $user, array $modul, bool $denganProfil): array
    {
        $items = [$this->item('Dashboard', 'dashboard', $user->role.'.dashboard')];

        foreach ($modul as $kunci) {
            [$label, $ikon] = self::MODUL[$kunci];
            $items[] = $this->item($label, $ikon, $user->role.'.'.$kunci);
        }

        if ($denganProfil) {
            $items[] = $this->item('Profil Peran', 'profil', 'role-profile.edit');
        }

        return [
            'panel' => null,
            'tema' => 'gelap',
            'peranLabel' => config("lokamitra.peran_registrasi.{$user->role}", ucfirst($user->role)),
            'items' => $items,
        ];
    }

    private function item(string $label, string $ikon, string $namaRoute): array
    {
        return [
            'label' => $label,
            'ikon' => $ikon,
            'href' => Route::has($namaRoute) ? route($namaRoute, [], false) : null,
        ];
    }
}

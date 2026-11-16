<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Format tanggal bahasa Indonesia untuk tampilan dashboard.
 * Semua method memakai copy() supaya objek tanggal milik model tidak berubah.
 */
class FormatTanggal
{
    // Contoh: ['hari' => '05', 'bulan' => 'NOV'] untuk lencana tanggal.
    public static function lencana(CarbonInterface $tanggal): array
    {
        $t = $tanggal->copy()->locale('id');

        return [
            'hari' => $t->format('d'),
            'bulan' => mb_strtoupper($t->translatedFormat('M')),
        ];
    }

    // Contoh: "Sabtu, 28 November 2026".
    public static function panjang(CarbonInterface $tanggal): string
    {
        return $tanggal->copy()->locale('id')->translatedFormat('l, d F Y');
    }

    // Contoh: "28 Nov 2026".
    public static function ringkas(CarbonInterface $tanggal): string
    {
        return $tanggal->copy()->locale('id')->translatedFormat('d M Y');
    }

    // Contoh: "23 Okt, 09:12".
    public static function denganJam(CarbonInterface $tanggal): string
    {
        return $tanggal->copy()->locale('id')->translatedFormat('d M, H:i');
    }

    // Contoh: "2 hari yang lalu".
    public static function relatif(CarbonInterface $tanggal): string
    {
        return $tanggal->copy()->locale('id')->diffForHumans();
    }
}

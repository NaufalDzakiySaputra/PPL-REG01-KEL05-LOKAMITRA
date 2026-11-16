<?php

namespace App\Services\Dashboard;

use App\Models\Event;
use App\Models\KeanggotaanEvent;
use App\Support\FormatTanggal;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;

/**
 * Katalog event untuk halaman Pengunjung (Figma "Tampilan Pengunjung"):
 * hanya event yang sudah diverifikasi Admin dan belum lewat tanggal selesai.
 */
class PengunjungDashboardService
{
    // Urutan warna kartu sementara (sebelum ada poster).
    public const JUMLAH_WARNA = 6;

    public function katalog(array $masukan): array
    {
        $q = trim((string) ($masukan['q'] ?? ''));
        $kategori = (string) ($masukan['kategori'] ?? '');
        $kota = (string) ($masukan['kota'] ?? '');
        $urut = ($masukan['urut'] ?? 'terdekat') === 'terbaru' ? 'terbaru' : 'terdekat';

        $query = Event::query()
            ->tersediaUmum()
            ->withCount([
                'keanggotaan as stakeholder_count' => fn ($k) => $k->where('status_keanggotaan', KeanggotaanEvent::STATUS_DITERIMA),
            ]);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('nama_event', 'like', "%{$q}%")
                    ->orWhere('lokasi', 'like', "%{$q}%");
            });
        }

        if ($kategori !== '') {
            $query->where('kategori_event', $kategori);
        }

        if ($kota !== '') {
            // Kota = bagian terakhir kolom lokasi setelah koma, mis. "Alun-alun, Banyumas".
            $query->where('lokasi', 'like', "%{$kota}");
        }

        $urut === 'terbaru'
            ? $query->orderByDesc('created_at')
            : $query->orderBy('tanggal_mulai');

        /** @var LengthAwarePaginator $halaman */
        $halaman = $query->paginate(9)->appends(array_filter([
            'q' => $q, 'kategori' => $kategori, 'kota' => $kota, 'urut' => $urut,
        ]));

        $halaman->through(fn (Event $e) => [
            'id' => $e->event_id,
            'nama' => $e->nama_event,
            'kategori' => $e->kategori_event,
            'lokasi' => $e->lokasi,
            'stakeholder' => $e->stakeholder_count,
            'warna' => $e->event_id % self::JUMLAH_WARNA,
            'tanggal' => FormatTanggal::lencana($e->tanggal_mulai),
            // Halaman detail publik dibuat di Tahap 7; sebelum itu tautan dinonaktifkan.
            'href' => Route::has('events.show') ? route('events.show', $e->event_id, false) : null,
        ]);

        return [
            'events' => $halaman,
            'filter' => ['q' => $q, 'kategori' => $kategori, 'kota' => $kota, 'urut' => $urut],
            'pilihan' => [
                'kategori' => $this->pilihanKategori(),
                'kota' => $this->pilihanKota(),
            ],
        ];
    }

    private function pilihanKategori(): array
    {
        return Event::query()->tersediaUmum()
            ->whereNotNull('kategori_event')
            ->distinct()
            ->orderBy('kategori_event')
            ->pluck('kategori_event')
            ->all();
    }

    private function pilihanKota(): array
    {
        return Event::query()->tersediaUmum()
            ->whereNotNull('lokasi')
            ->pluck('lokasi')
            ->map(fn (string $lokasi) => trim(last(explode(',', $lokasi))))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}

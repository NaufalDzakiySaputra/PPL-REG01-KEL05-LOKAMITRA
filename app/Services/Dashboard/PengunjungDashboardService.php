<?php

namespace App\Services\Dashboard;

use App\Models\DokumenEvent;
use App\Models\Event;
use App\Models\KeanggotaanEvent;
use App\Models\KontakDaruratEvent;
use App\Models\Timeline;
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

    // Detail satu event untuk halaman publik (deskripsi, jadwal, rundown,
    // dokumen/layout, regulasi, dan kontak darurat).
    public function detailEvent(Event $e): array
    {
        return [
            'event' => [
                'id' => $e->event_id,
                'nama' => $e->nama_event,
                'deskripsi' => $e->deskripsi,
                'kategori' => $e->kategori_event,
                'lokasi' => $e->lokasi,
                'stakeholder' => $e->keanggotaan()
                    ->where('status_keanggotaan', KeanggotaanEvent::STATUS_DITERIMA)
                    ->count(),
                'warna' => $e->event_id % self::JUMLAH_WARNA,
                'tanggal_mulai' => FormatTanggal::panjang($e->tanggal_mulai),
                'tanggal_selesai' => FormatTanggal::panjang($e->tanggal_selesai),
                // Batas pendaftaran = tanggal selesai event (belum ada kolom khusus).
                'daftar_sampai' => FormatTanggal::panjang($e->tanggal_selesai),
                'sedang_berjalan' => $e->tanggal_mulai->lte(today()),
                'informasi_regulasi' => $e->informasi_regulasi,
                'rundown' => $this->rundownPublik($e),
                'dokumen' => $this->dokumenPublik($e),
                'kontak_darurat' => $this->kontakDaruratPublik($e),
            ],
        ];
    }

    // Rundown acara untuk halaman publik, urut jam mulai.
    private function rundownPublik(Event $e): array
    {
        return $e->timeline()
            ->orderBy('waktu_mulai')
            ->get()
            ->map(fn (Timeline $t) => [
                'judul' => $t->judul_aktivitas,
                'deskripsi' => $t->deskripsi,
                'waktu_mulai' => $t->waktu_mulai
                    ? $t->waktu_mulai->locale('id')->translatedFormat('d M, H:i')
                    : null,
                'waktu_selesai' => $t->waktu_selesai
                    ? $t->waktu_selesai->locale('id')->translatedFormat('H:i')
                    : null,
            ])
            ->all();
    }

    // Daftar dokumen/layout yang bisa diunduh pengunjung lewat route terkontrol.
    private function dokumenPublik(Event $e): array
    {
        return $e->dokumen()
            ->orderBy('nama_dokumen')
            ->get()
            ->map(fn (DokumenEvent $d) => [
                'id' => $d->dokumen_event_id,
                'nama' => $d->nama_dokumen,
                'jenis' => $d->jenis ?? DokumenEvent::JENIS_DOKUMEN,
                'unduh_url' => route('events.dokumen', [$e->event_id, $d->dokumen_event_id], false),
            ])
            ->all();
    }

    // Kontak darurat (maksimal dua per event) untuk halaman publik.
    private function kontakDaruratPublik(Event $e): array
    {
        return $e->kontakDarurat()
            ->take(KontakDaruratEvent::MAKSIMAL_PER_EVENT)
            ->get()
            ->map(fn (KontakDaruratEvent $k) => [
                'nama' => $k->nama,
                'nomor' => $k->nomor_telepon,
            ])
            ->all();
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

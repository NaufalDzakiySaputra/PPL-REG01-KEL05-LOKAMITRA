<?php

namespace App\Http\Controllers\Api\V1\Publik;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventPublikResource;
use App\Models\Event;
use App\Models\KeanggotaanEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Informasi event publik (F-06 / PBI-035) untuk pengunjung tanpa login.
 * Hanya event yang sudah dipublikasikan Admin dan belum lewat tanggal selesai.
 */
class EventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $q = trim((string) $request->query('q', ''));
        $kategori = (string) $request->query('kategori', '');
        $kota = (string) $request->query('kota', '');
        $urut = $request->query('urut') === 'terbaru' ? 'terbaru' : 'terdekat';

        $query = Event::query()
            ->tersediaUmum()
            ->withCount([
                'keanggotaan as keanggotaan_count' => fn ($k) => $k
                    ->where('status_keanggotaan', KeanggotaanEvent::STATUS_DITERIMA),
            ]);

        if ($q !== '') {
            $query->where(function ($w) use ($q): void {
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

        return EventPublikResource::collection($query->paginate(9));
    }

    public function show(Event $event): JsonResponse
    {
        abort_unless(
            $event->status_event === Event::STATUS_DIPUBLIKASIKAN
                && $event->tanggal_selesai->endOfDay()->isFuture(),
            404,
        );

        return response()->json([
            'data' => new EventPublikResource(
                $this->muatInformasiPublik($event),
            ),
        ]);
    }

    // Urutan rundown dan dokumen ditentukan di query agar tidak bergantung pada
    // urutan penyimpanan.
    private function muatInformasiPublik(Event $event): Event
    {
        return $event->load([
            'timeline' => fn ($q) => $q->orderBy('waktu_mulai'),
            'dokumen' => fn ($q) => $q->orderBy('nama_dokumen'),
            'kontakDarurat' => fn ($q) => $q->orderBy('kontak_darurat_event_id'),
        ]);
    }
}

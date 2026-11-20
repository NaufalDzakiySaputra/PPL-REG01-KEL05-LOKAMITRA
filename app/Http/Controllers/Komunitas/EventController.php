<?php

namespace App\Http\Controllers\Komunitas;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Dashboard\PengunjungDashboardService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Informasi event publik untuk Komunitas Lokal (F-06 / PBI-040).
 * Hanya event yang sudah dipublikasikan Admin dan belum lewat tanggal selesai
 * yang boleh dilihat, termasuk jadwal kegiatan dan rundown-nya.
 */
class EventController extends Controller
{
    // Daftar event yang sudah dipublikasikan beserta filter katalog publik.
    public function index(Request $request, PengunjungDashboardService $katalog): Response
    {
        $data = $katalog->katalog($request->only(['q', 'kategori', 'kota', 'urut']));

        /** @var LengthAwarePaginator $halaman */
        $halaman = $data['events'];

        // Kartu diarahkan ke detail di area komunitas, bukan halaman publik.
        $halaman->through(fn (array $kartu) => [
            ...$kartu,
            'href' => route('komunitas.events.show', (int) $kartu['id'], false),
        ]);

        return Inertia::render('komunitas/events/Index', $data);
    }

    // Detail event beserta jadwal kegiatan yang sudah dipublikasikan.
    public function show(Event $event, PengunjungDashboardService $katalog): Response
    {
        abort_unless(
            $event->status_event === Event::STATUS_DIPUBLIKASIKAN
                && $event->tanggal_selesai->endOfDay()->isFuture(),
            404,
        );

        return Inertia::render('komunitas/events/Show', $katalog->detailEvent($event));
    }
}

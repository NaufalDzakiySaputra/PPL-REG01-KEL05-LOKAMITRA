<?php

namespace App\Http\Controllers\Akademisi;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Dashboard\PengunjungDashboardService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Informasi event publik untuk Akademisi (F-06 / PBI-041).
 * Hanya event yang sudah dipublikasikan Admin dan belum lewat tanggal selesai
 * yang boleh dilihat, termasuk informasi kegiatannya.
 */
class EventController extends Controller
{
    // Daftar event yang sudah dipublikasikan beserta filter katalog publik.
    public function index(Request $request, PengunjungDashboardService $katalog): Response
    {
        $data = $katalog->katalog($request->only(['q', 'kategori', 'kota', 'urut']));

        /** @var LengthAwarePaginator $halaman */
        $halaman = $data['events'];

        // Kartu diarahkan ke detail di area akademisi, bukan halaman publik.
        $halaman->through(fn (array $kartu) => [
            ...$kartu,
            'href' => route('akademisi.events.show', (int) $kartu['id'], false),
        ]);

        return Inertia::render('akademisi/events/Index', $data);
    }

    // Detail event beserta informasi kegiatan yang sudah dipublikasikan.
    public function show(Event $event, PengunjungDashboardService $katalog): Response
    {
        abort_unless(
            $event->status_event === Event::STATUS_DIPUBLIKASIKAN
                && $event->tanggal_selesai->endOfDay()->isFuture(),
            404,
        );

        return Inertia::render('akademisi/events/Show', $katalog->detailEvent($event));
    }
}

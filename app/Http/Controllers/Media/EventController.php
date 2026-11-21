<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Dashboard\PengunjungDashboardService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Informasi event publik untuk Media (F-06 / PBI-042).
 * Hanya event yang sudah dipublikasikan Admin dan belum lewat tanggal selesai
 * yang boleh dilihat, termasuk rundown dan kontak penyelenggaranya.
 */
class EventController extends Controller
{
    // Daftar event yang sudah dipublikasikan beserta filter katalog publik.
    public function index(Request $request, PengunjungDashboardService $katalog): Response
    {
        $data = $katalog->katalog($request->only(['q', 'kategori', 'kota', 'urut']));

        /** @var LengthAwarePaginator $halaman */
        $halaman = $data['events'];

        // Kartu diarahkan ke detail di area media, bukan halaman publik.
        $halaman->through(fn (array $kartu) => [
            ...$kartu,
            'href' => route('media.events.show', (int) $kartu['id'], false),
        ]);

        return Inertia::render('media/events/Index', $data);
    }

    // Detail event beserta rundown, kontak, dan identitas penyelenggara.
    public function show(Event $event, PengunjungDashboardService $katalog): Response
    {
        abort_unless(
            $event->status_event === Event::STATUS_DIPUBLIKASIKAN
                && $event->tanggal_selesai->endOfDay()->isFuture(),
            404,
        );

        $data = $katalog->detailEvent($event);

        // Data profil EO ini hanya ditambahkan di area media. Service katalog
        // sengaja tidak diubah agar halaman publik dan peran lain tetap sama.
        $data['event']['penyelenggara'] = [
            'nama' => $event->eo->nama_eo_organisasi,
            'website' => $event->eo->website_media_sosial,
            'alamat' => $event->eo->alamat_kantor,
        ];

        return Inertia::render('media/events/Show', $data);
    }
}

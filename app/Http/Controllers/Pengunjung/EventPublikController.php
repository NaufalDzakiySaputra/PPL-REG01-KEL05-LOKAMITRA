<?php

namespace App\Http\Controllers\Pengunjung;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Dashboard\PengunjungDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// Daftar event publik (F-06): dapat diakses tanpa login.
class EventPublikController extends Controller
{
    public function index(Request $request, PengunjungDashboardService $katalog): Response
    {
        return Inertia::render('Event/Index', $katalog->katalog(
            $request->only(['q', 'kategori', 'kota', 'urut'])
        ));
    }

    // Detail event publik: bisa dilihat tanpa login sebelum mendaftar.
    public function show(Event $event, PengunjungDashboardService $katalog): Response
    {
        abort_unless(
            $event->status_event === Event::STATUS_DIPUBLIKASIKAN
                && $event->tanggal_selesai->endOfDay()->isFuture(),
            404,
        );

        return Inertia::render('Event/Show', $katalog->detailEvent($event));
    }
}

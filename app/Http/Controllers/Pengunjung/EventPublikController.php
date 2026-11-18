<?php

namespace App\Http\Controllers\Pengunjung;

use App\Http\Controllers\Controller;
use App\Models\DokumenEvent;
use App\Models\Event;
use App\Services\Dashboard\PengunjungDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    // Unduh dokumen/layout milik event yang masih dipublikasikan.
    public function unduhDokumen(Event $event, DokumenEvent $dokumen): StreamedResponse
    {
        abort_unless($dokumen->event_id === $event->event_id, 404);
        abort_unless(
            $event->status_event === Event::STATUS_DIPUBLIKASIKAN
                && $event->tanggal_selesai->endOfDay()->isFuture(),
            404,
        );
        abort_unless(Storage::disk('public')->exists($dokumen->lokasi_file), 404);

        return Storage::disk('public')->download(
            $dokumen->lokasi_file,
            $dokumen->nama_dokumen,
        );
    }
}

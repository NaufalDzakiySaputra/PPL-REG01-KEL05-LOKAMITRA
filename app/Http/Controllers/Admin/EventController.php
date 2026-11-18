<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\FormatTanggal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    private const STATUS_DAFTAR = [
        Event::STATUS_MENUNGGU_VERIFIKASI,
        Event::STATUS_DIPUBLIKASIKAN,
        Event::STATUS_DITUTUP,
        Event::STATUS_DIARSIPKAN,
    ];

    public function index(Request $request): Response
    {
        $status = $request->query('status', Event::STATUS_MENUNGGU_VERIFIKASI);

        if (! in_array($status, self::STATUS_DAFTAR, true)) {
            $status = Event::STATUS_MENUNGGU_VERIFIKASI;
        }

        $query = Event::query()->with('eo')->where('status_event', $status);

        match ($status) {
            Event::STATUS_MENUNGGU_VERIFIKASI => $query->oldest('updated_at'),
            Event::STATUS_DIPUBLIKASIKAN => $query->latest('tanggal_diverifikasi'),
            Event::STATUS_DITUTUP => $query->latest('tanggal_ditutup'),
            Event::STATUS_DIARSIPKAN => $query->latest('tanggal_diarsipkan'),
        };

        $events = $query
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Event $e) => [
                'id' => $e->event_id,
                'nama' => $e->nama_event,
                'kategori' => $e->kategori_event,
                'lokasi' => $e->lokasi,
                'periode' => $this->periode($e),
                'eo' => $e->eo?->nama_eo_organisasi,
                'status' => $e->status_event,
                'diperbarui' => FormatTanggal::denganJam($e->updated_at),
                'tanggal_ditutup' => $e->tanggal_ditutup
                    ? FormatTanggal::denganJam($e->tanggal_ditutup)
                    : null,
                'tanggal_diarsipkan' => $e->tanggal_diarsipkan
                    ? FormatTanggal::denganJam($e->tanggal_diarsipkan)
                    : null,
            ]);

        return Inertia::render('admin/events/Index', [
            'events' => $events,
            'filter' => ['status' => $status],
            'ringkasan' => [
                'menunggu' => Event::where('status_event', Event::STATUS_MENUNGGU_VERIFIKASI)->count(),
                'terverifikasi' => Event::where('status_event', Event::STATUS_DIPUBLIKASIKAN)->count(),
                'ditutup' => Event::where('status_event', Event::STATUS_DITUTUP)->count(),
                'diarsip' => Event::where('status_event', Event::STATUS_DIARSIPKAN)->count(),
            ],
        ]);
    }

    public function arsipkan(Event $event): RedirectResponse
    {
        // Update atomik: hanya berhasil jika status masih ditutup.
        $berhasil = Event::query()
            ->whereKey($event->getKey())
            ->where('status_event', Event::STATUS_DITUTUP)
            ->update([
                'status_event' => Event::STATUS_DIARSIPKAN,
                'tanggal_diarsipkan' => now(),
            ]);

        Inertia::flash('toast', $berhasil
            ? ['type' => 'success', 'message' => "Event \"{$event->nama_event}\" berhasil diarsipkan dan dapat dilihat di tab Arsip."]
            : ['type' => 'error', 'message' => 'Hanya event yang sudah ditutup EO yang dapat diarsipkan.']);

        return back();
    }

    public function verifikasi(Request $request, Event $event): RedirectResponse
    {
        $admin = $request->user()->admin;

        abort_if($admin === null, 403, 'Profil Admin tidak ditemukan.');

        $berhasil = Event::query()
            ->whereKey($event->getKey())
            ->where('status_event', Event::STATUS_MENUNGGU_VERIFIKASI)
            ->update([
                'status_event' => Event::STATUS_DIPUBLIKASIKAN,
                'diverifikasi_oleh' => $admin->admin_id,
                'tanggal_diverifikasi' => now(),
            ]);

        Inertia::flash('toast', $berhasil
           ? ['type' => 'success', 'message' => "Event \"{$event->nama_event}\" berhasil diverifikasi dan kini tampil di daftar publik."]
           : ['type' => 'error', 'message' => 'Event ini tidak sedang menunggu verifikasi.']);

        return back();
    }

    private function periode(Event $event): string
    {
        $mulai = FormatTanggal::ringkas($event->tanggal_mulai);

        return $event->tanggal_selesai->isSameDay($event->tanggal_mulai)
            ? $mulai
            : $mulai.' - '.FormatTanggal::ringkas($event->tanggal_selesai);
    }
}

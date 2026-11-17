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
    public function index(Request $request): Response
    {
        $status = $request->query('status', Event::STATUS_MENUNGGU_VERIFIKASI);

        if (! in_array($status, [Event::STATUS_MENUNGGU_VERIFIKASI, Event::STATUS_DIPUBLIKASIKAN], true)) {
            $status = Event::STATUS_MENUNGGU_VERIFIKASI;
        }

        $events = Event::query()
            ->with('eo')
            ->where('status_event', $status)
            ->when(
                $status === Event::STATUS_MENUNGGU_VERIFIKASI,
                fn ($q) => $q->oldest('updated_at'),
                fn ($q) => $q->latest('tanggal_diverifikasi'),
            )
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
            ]);

        return Inertia::render('admin/events/Index', [
            'events' => $events,
            'filter' => ['status' => $status],
            'ringkasan' => [
                'menunggu' => Event::where('status_event', Event::STATUS_MENUNGGU_VERIFIKASI)->count(),
                'terverifikasi' => Event::where('status_event', Event::STATUS_DIPUBLIKASIKAN)->count(),
            ],
        ]);
    }
    
    public function verifikasi(Request $request, Event $event):  RedirectResponse
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

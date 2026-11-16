<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Eo\StoreEventRequest;
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
        $eo = $request->user()->eo;

        abort_if($eo === null, 403, 'Profil EO tidak ditemukan.');

        $events = $eo->events()
            ->latest('created_at')
            ->paginate(10)
            ->through(fn (Event $event) => [
                'id' => $event->event_id,
                'nama' => $event->nama_event,
                'kategori' => $event->kategori_event,
                'lokasi' => $event->lokasi,
                'periode' => $this->periode($event),
                'status' => $event->status_event,
            ]);

        return Inertia::render('eo/events/Index', ['events' => $events]);
    }

    public function create(Request $request): Response
    {
        abort_if($request->user()->eo === null, 403, 'Profil EO tidak ditemukan.');
        return Inertia::render('eo/events/Create', [
            'kategori' => config('lokamitra.kategori_event',[]),
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $event = new Event($request->validated());
        $event->eo_id = $request->user()->eo->eo_id;
        $event->status_event = Event::STATUS_DRAFT;
        $event->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Event \"{$event->nama_event}\" berhasil dibuat sebagai draft.",
        ]);
        
        return to_route('eo.events.index');
    }

    private function periode(Event $event): string
    {
        $mulai = FormatTanggal::ringkas($event->tanggal_mulai);

        return $event->tanggal_selesai->isSameDay($event->tanggal_mulai)
            ? $mulai
            : $mulai.' - '.FormatTanggal::ringkas($event->tanggal_selesai);

    }
    
}

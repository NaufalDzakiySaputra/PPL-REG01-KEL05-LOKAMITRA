<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Eo\StoreEventRequest;
use App\Http\Requests\Eo\UpdateEventRequest;
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
            'kategori' => config('lokamitra.kategori_event', []),
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

    public function show(Request $request, Event $event): Response
    {
        $this->pastikanPemilik($request, $event);

        return Inertia::render('eo/events/Show', [
            'event' => $this->detail($event),
        ]);
    }

    public function edit(Request $request, Event $event): Response
    {
        $this->pastikanPemilik($request, $event);
        abort_unless($event->dapatDiubah(), 403, 'Event ini tidak dapat diubah.');

        return Inertia::render('eo/events/Edit', [
            'event' => $this->detail($event),
            'kategori' => config('lokamitra.kategori_event', []),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        // validated() hanya berisi field form, jadi eo_id dan status tidak bisa diubah.
        $event->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Event \"{$event->nama_event}\" berhasil diperbarui.",
        ]);

        return to_route('eo.events.show', $event);
    }

    private function pastikanPemilik(Request $request, Event $event): void
    {
        abort_unless(
            $request->user()->eo?->eo_id === $event->eo_id,
            403,
            'Anda bukan pemilik event ini.'
        );
    }

    private function detail(Event $event): array
    {
        return [
            'id' => $event->event_id,
            'nama' => $event->nama_event,
            'kategori' => $event->kategori_event,
            'lokasi' => $event->lokasi,
            'deskripsi' => $event->deskripsi,
            'tanggal_mulai' => $event->tanggal_mulai->toDateString(),
            'tanggal_selesai' => $event->tanggal_selesai->toDateString(),
            'periode' => $this->periode($event),
            'status' => $event->status_event,
            'dapat_diubah' => $event->dapatDiubah(),
            'diperbarui' => FormatTanggal::denganJam($event->updated_at),
        ];
    }
}

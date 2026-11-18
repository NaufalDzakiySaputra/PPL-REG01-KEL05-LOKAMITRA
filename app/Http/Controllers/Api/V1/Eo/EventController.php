<?php

namespace App\Http\Controllers\Api\V1\Eo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Eo\StoreEventRequest;
use App\Http\Requests\Eo\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $eo = $request->user()?->eo;
        abort_if($eo === null, 403, 'Profil EO tidak ditemukan.');

        $events = $eo->events()->latest('created_at')->paginate(10);

        return EventResource::collection($events);
    }

    public function show(Request $request, Event $event): JsonResponse
    {
        $this->pastikanPemilik($request, $event);

        return response()->json([
            'data' => new EventResource($event),
        ]);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $eo = $request->user()?->eo;
        abort_if($eo === null, 403, 'Profil EO tidak ditemukan.');

        $event = new Event($request->validated());
        $event->eo_id = $eo->eo_id;
        $event->status_event = Event::STATUS_MENUNGGU_VERIFIKASI;
        $event->save();

        return response()->json([
            'message' => 'Event baru berhasil dibuat dan menunggu verifikasi.',
            'data' => new EventResource($event),
        ], 201);
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $this->pastikanPemilik($request, $event);
        abort_unless($event->dapatDiubah(), 403, 'Event ini tidak dapat diubah.');

        $event->update($request->validated());

        return response()->json([
            'message' => 'Informasi event berhasil diperbarui.',
            'data' => new EventResource($event),
        ]);
    }

    private function pastikanPemilik(Request $request, Event $event): void
    {
        $eo = $request->user()?->eo;
        $eoId = $eo?->eo_id ?? $eo?->id;

        abort_unless($event->eo_id === $eoId, 403, 'Anda bukan pemilik event ini.');
    }
}

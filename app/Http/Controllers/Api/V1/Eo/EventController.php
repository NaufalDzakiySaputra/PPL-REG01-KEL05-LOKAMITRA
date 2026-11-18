<?php

namespace App\Http\Controllers\Api\V1\Eo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Eo\StoreEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    /**
     * Membuat event baru milik EO yang sedang login.
     */
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
}

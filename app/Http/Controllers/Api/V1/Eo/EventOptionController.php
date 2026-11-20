<?php

namespace App\Http\Controllers\Api\V1\Eo;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventOptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $eo = $request->user()->eo;

        abort_if($eo === null, 403, 'Profil EO tidak ditemukan.');

        $events = $eo->events()
            ->latest('created_at')
            ->get([
                'event_id',
                'nama_event',
                'status_event',
            ])
            ->map(fn (Event $event) => [
                'id' => $event->event_id,
                'nama' => $event->nama_event,
                'status' => $event->status_event,
            ]);

        return response()->json([
            'data' => $events,
        ]);
    }
}

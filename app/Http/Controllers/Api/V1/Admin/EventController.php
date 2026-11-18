<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->query('status', Event::STATUS_MENUNGGU_VERIFIKASI);

        $query = Event::query()->with('eo');

        if ($status !== null && $status !== '') {
            $query->where('status_event', $status);
        }

        $events = $query->oldest('updated_at')->paginate(10);

        return EventResource::collection($events);
    }

    public function verifikasi(Request $request, Event $event): JsonResponse
    {
        $admin = $request->user()->admin;
        abort_if($admin === null, 403, 'Profil Admin tidak ditemukan.');

        if ($event->status_event !== Event::STATUS_MENUNGGU_VERIFIKASI) {
            return response()->json([
                'message' => 'Event ini tidak sedang menunggu verifikasi.',
            ], 422);
        }

        $event->update([
            'status_event' => Event::STATUS_DIPUBLIKASIKAN,
            'diverifikasi_oleh' => $admin->admin_id,
            'tanggal_diverifikasi' => now(),
        ]);

        return response()->json([
            'message' => 'Event berhasil diverifikasi.',
            'data' => new EventResource($event->refresh()),
        ]);
    }
}

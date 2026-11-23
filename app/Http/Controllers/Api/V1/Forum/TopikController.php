<?php

namespace App\Http\Controllers\Api\V1\Forum;

use App\Http\Controllers\Controller;
use App\Http\Requests\Forum\StoreTopikRequest;
use App\Http\Resources\TopikResource;
use App\Models\Event;
use App\Models\Forum;
use App\Models\User;
use App\Services\ForumAksesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopikController extends Controller
{
    public function __construct(
        private readonly ForumAksesService $akses
    ) {}

    /** Daftar topik forum pada sebuah event. */
    public function index(Request $request, Event $event): JsonResponse
    {
        $this->akses->pastikanBolehMengakses($request->user(), $event);

        $topik = Forum::query()
            ->where('event_id', $event->event_id)
            ->with('pembuat')
            ->withCount('pesan')
            ->latest('tanggal_dibuat')
            ->get();

        return response()->json([
            'data' => TopikResource::collection($topik),
        ]);
    }

    /** EO membuat topik baru untuk event miliknya (PBI-043). */
    public function store(StoreTopikRequest $request, Event $event): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->role === User::ROLE_EO, 403, 'Hanya Event Organizer yang dapat membuat topik.');
        abort_unless($this->akses->adalahPemilikEvent($user, $event), 404, 'Forum pada event ini tidak ditemukan.');

        $this->akses->pastikanBolehMenulis($user, $event);

        $forum = Forum::create([
            'event_id' => $event->event_id,
            'pembuat_akun_id' => $user->id,
            'judul' => $request->validated('judul'),
            'deskripsi' => $request->validated('deskripsi'),
            'tanggal_dibuat' => now(),
        ]);

        return response()->json([
            'message' => 'Topik diskusi berhasil dibuat.',
            'data' => new TopikResource($forum->load('pembuat')),
        ], 201);
    }
}

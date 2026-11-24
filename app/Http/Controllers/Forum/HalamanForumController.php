<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use App\Services\ForumAksesService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman Inertia forum diskusi (F-07). Route dibuat per role agar menu
 * "Forum Diskusi" di RoleMenuService otomatis aktif, tetapi komponen
 * halamannya dipakai bersama.
 */
class HalamanForumController extends Controller
{
    public function index(Request $request, ForumAksesService $akses): Response
    {
        $events = $akses->eventsDiikuti($request->user())->map(fn (Event $event) => [
            'id' => $event->event_id,
            'nama' => $event->nama_event,
            'status' => $event->status_event,
        ])->values();

        return Inertia::render('forum/Index', [
            'events' => $events,
            'awalan' => $request->user()->role,
            'bolehBuatTopik' => $request->user()->role === User::ROLE_EO,
        ]);
    }

    public function show(Request $request, Event $event, ForumAksesService $akses): Response
    {
        $akses->pastikanBolehMengakses($request->user(), $event);

        return Inertia::render('forum/Show', [
            'event' => [
                'id' => $event->event_id,
                'nama' => $event->nama_event,
            ],
            'awalan' => $request->user()->role,
            'topikAwal' => is_numeric($request->query('topik'))
                ? (int) $request->query('topik')
                : null,
        ]);
    }
}

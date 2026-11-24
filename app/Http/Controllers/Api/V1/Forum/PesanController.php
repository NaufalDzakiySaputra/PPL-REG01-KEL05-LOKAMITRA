<?php

namespace App\Http\Controllers\Api\V1\Forum;

use App\Http\Controllers\Controller;
use App\Http\Requests\Forum\StorePesanRequest;
use App\Http\Resources\PesanResource;
use App\Http\Resources\TopikResource;
use App\Models\Forum;
use App\Models\PesanForum;
use App\Services\ForumAksesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PesanController extends Controller
{
    public function __construct(
        private readonly ForumAksesService $akses
    ) {}

    /** Detail utas beserta seluruh tanggapan. */
    public function show(Request $request, Forum $forum): JsonResponse
    {
        $this->akses->pastikanBolehMengakses($request->user(), $forum->event);

        $forum->load('pembuat');

        $pesan = $forum->pesan()
            ->with('pengirim')
            ->oldest('waktu_kirim')
            ->get();

        return response()->json([
            'data' => [
                'topik' => new TopikResource($forum),
                'pesan' => PesanResource::collection($pesan),
            ],
        ]);
    }

    /** Peserta forum mengirim tanggapan (PBI-044, 046, 047, 048). */
    public function store(StorePesanRequest $request, Forum $forum): JsonResponse
    {
        $this->akses->pastikanBolehMenulis($request->user(), $forum->event);

        $pesan = PesanForum::create([
            'forum_id' => $forum->forum_id,
            'pengirim_akun_id' => $request->user()->id,
            'isi_pesan' => $request->validated('isi_pesan'),
            'waktu_kirim' => now(),
        ]);

        return response()->json([
            'message' => 'Tanggapan berhasil dikirim.',
            'data' => new PesanResource($pesan->load('pengirim')),
        ], 201);
    }
}

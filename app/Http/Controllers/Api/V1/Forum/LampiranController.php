<?php

namespace App\Http\Controllers\Api\V1\Forum;

use App\Http\Controllers\Controller;
use App\Http\Requests\Forum\StoreLampiranRequest;
use App\Http\Resources\LampiranResource;
use App\Models\Forum;
use App\Models\LampiranForum;
use App\Models\PesanForum;
use App\Services\ForumAksesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class LampiranController extends Controller
{
    public function __construct(
        private readonly ForumAksesService $akses
    ) {}

    /** Akademisi melampirkan berkas pada sebuah tanggapan di forum. */
    public function store(StoreLampiranRequest $request, Forum $forum): JsonResponse
    {
        $this->akses->pastikanBolehMenulis($request->user(), $forum->event);

        $pesan = PesanForum::query()
            ->whereKey($request->validated('pesan_id'))
            ->where('forum_id', $forum->forum_id)
            ->firstOrFail();

        $file = $request->file('file_lampiran');
        $path = $file->store("lampiran-forum/{$forum->forum_id}", 'local');

        try {
            $lampiran = LampiranForum::create([
                'pesan_forum_id' => $pesan->pesan_forum_id,
                'pengunggah_akun_id' => $request->user()->id,
                'nama_file' => $file->getClientOriginalName(),
                'lokasi_file' => $path,
                'format_file' => strtolower($file->getClientOriginalExtension()),
                'ukuran_file' => $file->getSize(),
                'waktu_upload' => now(),
            ]);
        } catch (Throwable $exception) {
            // Jangan meninggalkan berkas yatim bila penyimpanan DB gagal.
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        return response()->json([
            'message' => 'Lampiran berhasil diunggah.',
            'data' => new LampiranResource($lampiran->load(['pengunggah', 'pesan'])),
        ], 201);
    }

    /** Peserta forum yang berwenang mengunduh berkas lampiran. */
    public function download(
        Request $request,
        Forum $forum,
        string $lampiran
    ): StreamedResponse {
        $this->akses->pastikanBolehMengakses($request->user(), $forum->event);

        $lampiran = $this->temukanLampiran($forum, $lampiran);

        abort_unless(
            Storage::disk('local')->exists($lampiran->lokasi_file),
            404,
            'Berkas lampiran tidak ditemukan.'
        );

        return Storage::disk('local')->download(
            $lampiran->lokasi_file,
            basename($lampiran->nama_file)
        );
    }

    private function temukanLampiran(Forum $forum, mixed $id): LampiranForum
    {
        return LampiranForum::query()
            ->whereKey($id)
            ->whereHas('pesan', fn ($query) => $query->where('forum_id', $forum->forum_id))
            ->firstOrFail();
    }
}

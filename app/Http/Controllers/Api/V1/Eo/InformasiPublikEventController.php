<?php

namespace App\Http\Controllers\Api\V1\Eo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Eo\StoreDokumenEventRequest;
use App\Http\Requests\Eo\StoreKontakDaruratRequest;
use App\Http\Requests\Eo\StoreRundownRequest;
use App\Http\Requests\Eo\UpdateDokumenEventRequest;
use App\Http\Requests\Eo\UpdateInformasiRegulasiRequest;
use App\Http\Requests\Eo\UpdateKontakDaruratRequest;
use App\Http\Resources\DokumenEventResource;
use App\Http\Resources\KontakDaruratResource;
use App\Http\Resources\RundownResource;
use App\Models\DokumenEvent;
use App\Models\Event;
use App\Models\KontakDaruratEvent;
use App\Models\Timeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Pengelolaan informasi publik event (PBI-035, PBI-036): rundown,
 * layout/dokumen, regulasi, dan kontak darurat yang nanti tampil di halaman
 * detail pengunjung.
 *
 * Semua aksi hanya untuk EO pemilik dan selama event masih dapat diubah.
 */
class InformasiPublikEventController extends Controller
{
    public function show(Request $request, Event $event): JsonResponse
    {
        $this->pastikanPemilik($request, $event);

        $event->load([
            'timeline' => fn ($q) => $q->orderBy('waktu_mulai'),
            'dokumen' => fn ($q) => $q->orderBy('nama_dokumen'),
            'kontakDarurat' => fn ($q) => $q->orderBy('kontak_darurat_event_id'),
        ]);

        return response()->json([
            'data' => [
                'informasi_regulasi' => $event->informasi_regulasi,
                'rundown' => RundownResource::collection($event->timeline),
                'dokumen' => DokumenEventResource::collection($event->dokumen),
                'kontak_darurat' => KontakDaruratResource::collection($event->kontakDarurat),
                'batas_kontak_darurat' => KontakDaruratEvent::MAKSIMAL_PER_EVENT,
                'dapat_diubah' => $event->dapatDiubah(),
            ],
        ]);
    }

    // Memperbarui informasi & regulasi tanpa memaksa EO mengirim ulang seluruh
    // data event (PBI-036).
    public function updateRegulasi(UpdateInformasiRegulasiRequest $request, Event $event): JsonResponse
    {
        $event->update([
            'informasi_regulasi' => $request->validated('informasi_regulasi'),
        ]);

        return response()->json([
            'message' => 'Informasi & regulasi berhasil diperbarui.',
            'data' => [
                'informasi_regulasi' => $event->informasi_regulasi,
            ],
        ]);
    }

    public function storeRundown(StoreRundownRequest $request, Event $event): JsonResponse
    {
        $timeline = $event->timeline()->create($request->validated());

        return response()->json([
            'message' => 'Rundown berhasil ditambahkan.',
            'data' => new RundownResource($timeline),
        ], 201);
    }

    public function updateRundown(
        StoreRundownRequest $request,
        Event $event,
        Timeline $rundown,
    ): JsonResponse {
        $this->pastikanPemilik($request, $event);
        $this->pastikanDapatDiubah($event);
        $this->pastikanMilikEvent($event, $rundown);

        $rundown->update($request->validated());

        return response()->json([
            'message' => 'Rundown berhasil diperbarui.',
            'data' => new RundownResource($rundown->refresh()),
        ]);
    }

    public function destroyRundown(Request $request, Event $event, Timeline $rundown): JsonResponse
    {
        $this->pastikanPemilik($request, $event);
        $this->pastikanDapatDiubah($event);
        $this->pastikanMilikEvent($event, $rundown);

        $rundown->delete();

        return response()->json(['message' => 'Rundown berhasil dihapus.']);
    }

    public function storeDokumen(StoreDokumenEventRequest $request, Event $event): JsonResponse
    {
        $berkas = $request->file('file');
        $lokasi = $berkas->store("dokumen-event/{$event->event_id}", 'local');

        $dokumen = $event->dokumen()->create([
            'pengunggah_akun_id' => $request->user()->id,
            'nama_dokumen' => $request->validated('nama_dokumen'),
            'jenis' => $request->validated('jenis'),
            'lokasi_file' => $lokasi,
            'tanggal_upload' => now(),
        ]);

        return response()->json([
            'message' => 'Dokumen berhasil diunggah.',
            'data' => new DokumenEventResource($dokumen),
        ], 201);
    }

    // Memperbarui nama/jenis dokumen dan/atau mengganti berkasnya (PBI-036).
    // Berkas lama dihapus agar disk tidak menyimpan file yatim.
    public function updateDokumen(
        UpdateDokumenEventRequest $request,
        Event $event,
        DokumenEvent $dokumen,
    ): JsonResponse {
        $this->pastikanPemilik($request, $event);
        $this->pastikanDapatDiubah($event);
        abort_unless($dokumen->event_id === $event->event_id, 404);

        $perubahan = [
            'nama_dokumen' => $request->validated('nama_dokumen'),
            'jenis' => $request->validated('jenis'),
        ];

        if ($request->hasFile('file')) {
            $lokasi = $request->file('file')->store("dokumen-event/{$event->event_id}", 'local');

            Storage::disk('local')->delete($dokumen->lokasi_file);

            $perubahan['lokasi_file'] = $lokasi;
            $perubahan['tanggal_upload'] = now();
        }

        $dokumen->update($perubahan);

        return response()->json([
            'message' => 'Dokumen berhasil diperbarui.',
            'data' => new DokumenEventResource($dokumen->refresh()),
        ]);
    }

    public function destroyDokumen(Request $request, Event $event, DokumenEvent $dokumen): JsonResponse
    {
        $this->pastikanPemilik($request, $event);
        $this->pastikanDapatDiubah($event);
        abort_unless($dokumen->event_id === $event->event_id, 404);

        Storage::disk('local')->delete($dokumen->lokasi_file);
        $dokumen->delete();

        return response()->json(['message' => 'Dokumen berhasil dihapus.']);
    }

    public function storeKontak(StoreKontakDaruratRequest $request, Event $event): JsonResponse
    {
        $kontak = $event->kontakDarurat()->create($request->validated());

        return response()->json([
            'message' => 'Kontak darurat berhasil ditambahkan.',
            'data' => new KontakDaruratResource($kontak),
        ], 201);
    }

    public function updateKontak(
        UpdateKontakDaruratRequest $request,
        Event $event,
        KontakDaruratEvent $kontak,
    ): JsonResponse {
        $this->pastikanPemilik($request, $event);
        $this->pastikanDapatDiubah($event);
        abort_unless($kontak->event_id === $event->event_id, 404);

        $kontak->update($request->validated());

        return response()->json([
            'message' => 'Kontak darurat berhasil diperbarui.',
            'data' => new KontakDaruratResource($kontak->refresh()),
        ]);
    }

    public function destroyKontak(Request $request, Event $event, KontakDaruratEvent $kontak): JsonResponse
    {
        $this->pastikanPemilik($request, $event);
        $this->pastikanDapatDiubah($event);
        abort_unless($kontak->event_id === $event->event_id, 404);

        $kontak->delete();

        return response()->json(['message' => 'Kontak darurat berhasil dihapus.']);
    }

    private function pastikanPemilik(Request $request, Event $event): void
    {
        $eo = $request->user()?->eo;

        abort_unless(
            $eo !== null && $event->eo_id === $eo->eo_id,
            403,
            'Anda bukan pemilik event ini.',
        );
    }

    private function pastikanDapatDiubah(Event $event): void
    {
        abort_unless($event->dapatDiubah(), 403, 'Event ini tidak dapat diubah.');
    }

    private function pastikanMilikEvent(Event $event, Timeline $rundown): void
    {
        abort_unless($rundown->event_id === $event->event_id, 404);
    }
}

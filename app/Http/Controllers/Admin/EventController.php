<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DokumenEvent;
use App\Models\Eo;
use App\Models\Event;
use App\Models\KontakDaruratEvent;
use App\Models\Timeline;
use App\Support\FormatTanggal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventController extends Controller
{
    private const STATUS_DAFTAR = [
        Event::STATUS_MENUNGGU_VERIFIKASI,
        Event::STATUS_DIPUBLIKASIKAN,
        Event::STATUS_DITUTUP,
        Event::STATUS_DIARSIPKAN,
    ];

    public function index(Request $request): Response
    {
        $status = $request->query('status', Event::STATUS_MENUNGGU_VERIFIKASI);

        if (! in_array($status, self::STATUS_DAFTAR, true)) {
            $status = Event::STATUS_MENUNGGU_VERIFIKASI;
        }

        $query = Event::query()->with('eo')->where('status_event', $status);

        match ($status) {
            Event::STATUS_MENUNGGU_VERIFIKASI => $query->oldest('updated_at'),
            Event::STATUS_DIPUBLIKASIKAN => $query->latest('tanggal_diverifikasi'),
            Event::STATUS_DITUTUP => $query->latest('tanggal_ditutup'),
            Event::STATUS_DIARSIPKAN => $query->latest('tanggal_diarsipkan'),
        };

        $events = $query
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Event $e) => [
                'id' => $e->event_id,
                'nama' => $e->nama_event,
                'kategori' => $e->kategori_event,
                'lokasi' => $e->lokasi,
                'periode' => $this->periode($e),
                'eo' => $e->eo?->nama_eo_organisasi,
                'status' => $e->status_event,
                'diperbarui' => FormatTanggal::denganJam($e->updated_at),
                'tanggal_ditutup' => $e->tanggal_ditutup
                    ? FormatTanggal::denganJam($e->tanggal_ditutup)
                    : null,
                'tanggal_diarsipkan' => $e->tanggal_diarsipkan
                    ? FormatTanggal::denganJam($e->tanggal_diarsipkan)
                    : null,
            ]);

        return Inertia::render('admin/events/Index', [
            'events' => $events,
            'filter' => ['status' => $status],
            'ringkasan' => [
                'menunggu' => Event::where('status_event', Event::STATUS_MENUNGGU_VERIFIKASI)->count(),
                'terverifikasi' => Event::where('status_event', Event::STATUS_DIPUBLIKASIKAN)->count(),
                'ditutup' => Event::where('status_event', Event::STATUS_DITUTUP)->count(),
                'diarsip' => Event::where('status_event', Event::STATUS_DIARSIPKAN)->count(),
            ],
        ]);
    }

    /**
     * Halaman pemeriksaan informasi publik event (PBI-037).
     * Admin dapat meninjau event pada status apa pun, termasuk draft.
     */
    public function show(Event $event): Response
    {
        $event->load(['eo.akun', 'adminVerifikator.akun']);

        $rundown = Timeline::query()
            ->where('event_id', $event->event_id)
            ->orderBy('waktu_mulai')
            ->get();

        $dokumen = DokumenEvent::query()
            ->where('event_id', $event->event_id)
            ->orderBy('nama_dokumen')
            ->get();

        $kontak = KontakDaruratEvent::query()
            ->where('event_id', $event->event_id)
            ->orderBy('kontak_darurat_event_id')
            ->get();

        return Inertia::render('admin/events/Show', [
            'event' => [
                'id' => $event->event_id,
                'nama' => $event->nama_event,
                'kategori' => $event->kategori_event,
                'lokasi' => $event->lokasi,
                'deskripsi' => $event->deskripsi,
                'informasi_regulasi' => $event->informasi_regulasi,
                'periode' => $this->periode($event),
                'tanggal_mulai' => $event->tanggal_mulai->toDateString(),
                'tanggal_selesai' => $event->tanggal_selesai->toDateString(),
                'status' => $event->status_event,
                'status_label' => $this->statusLabel($event),
                'dibuat' => FormatTanggal::denganJam($event->created_at),
                'diperbarui' => FormatTanggal::denganJam($event->updated_at),
                'diverifikasi' => $event->tanggal_diverifikasi
                    ? FormatTanggal::denganJam($event->tanggal_diverifikasi)
                    : null,
                'diverifikasi_oleh' => $event->adminVerifikator?->akun?->name,
                'ditutup' => $event->tanggal_ditutup
                    ? FormatTanggal::denganJam($event->tanggal_ditutup)
                    : null,
                'diarsipkan' => $event->tanggal_diarsipkan
                    ? FormatTanggal::denganJam($event->tanggal_diarsipkan)
                    : null,
                'dapat_diverifikasi' => $event->status_event === Event::STATUS_MENUNGGU_VERIFIKASI,
                'dapat_diarsipkan' => $event->status_event === Event::STATUS_DITUTUP,
            ],
            'eo' => [
                'nama_organisasi' => $event->eo?->nama_eo_organisasi,
                'nama_penanggung_jawab' => $event->eo?->akun?->name,
                'email' => $event->eo?->akun?->email,
                'nomor_telepon' => $event->eo?->akun?->nomor_telepon,
                'status_verifikasi' => $event->eo?->status_verifikasi,
                'status_verifikasi_label' => $this->statusEoLabel($event->eo),
            ],
            'rundown' => $rundown
                ->map(fn (Timeline $butir) => [
                    'id' => $butir->timeline_id,
                    'judul' => $butir->judul_aktivitas,
                    'deskripsi' => $butir->deskripsi,
                    'waktu_mulai' => $butir->waktu_mulai
                        ? FormatTanggal::denganJam($butir->waktu_mulai)
                        : null,
                    'waktu_selesai' => $butir->waktu_selesai
                        ? FormatTanggal::denganJam($butir->waktu_selesai)
                        : null,
                ])
                ->all(),
            'dokumen' => $dokumen
                ->map(fn (DokumenEvent $butir) => [
                    'id' => $butir->dokumen_event_id,
                    'nama' => $butir->nama_dokumen,
                    'jenis' => $butir->jenis ?? DokumenEvent::JENIS_DOKUMEN,
                    'diunggah_pada' => $butir->tanggal_upload
                        ? FormatTanggal::denganJam($butir->tanggal_upload)
                        : null,
                    'pratinjau_url' => route('admin.events.dokumen.pratinjau', [
                        $event->event_id,
                        $butir->dokumen_event_id,
                    ], false),
                    'unduh_url' => route('admin.events.dokumen.unduh', [
                        $event->event_id,
                        $butir->dokumen_event_id,
                    ], false),
                ])
                ->all(),
            'kontak_darurat' => $kontak
                ->map(fn (KontakDaruratEvent $butir) => [
                    'id' => $butir->kontak_darurat_event_id,
                    'nama' => $butir->nama,
                    'nomor_telepon' => $butir->nomor_telepon,
                ])
                ->all(),
        ]);
    }

    /**
     * Unduh dokumen/layout event untuk pemeriksaan admin.
     * Berlaku juga saat event belum dipublikasikan karena berkas disimpan privat.
     */
    public function unduhDokumen(Event $event, DokumenEvent $dokumen): StreamedResponse
    {
        $this->pastikanMilikEvent($event, $dokumen);

        abort_unless(Storage::disk('local')->exists($dokumen->lokasi_file), 404);

        return Storage::disk('local')->download(
            $dokumen->lokasi_file,
            $dokumen->nama_dokumen,
        );
    }

    /** Pratinjau inline dokumen pemeriksaan; hanya PDF dan gambar yang aman. */
    public function pratinjauDokumen(Event $event, DokumenEvent $dokumen): BinaryFileResponse
    {
        $this->pastikanMilikEvent($event, $dokumen);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($dokumen->lokasi_file), 404);

        $mime = $disk->mimeType($dokumen->lokasi_file);
        abort_unless(in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true), 415);

        return response()->file($disk->path($dokumen->lokasi_file), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function arsipkan(Event $event): RedirectResponse
    {
        // Update atomik: hanya berhasil jika status masih ditutup.
        $berhasil = Event::query()
            ->whereKey($event->getKey())
            ->where('status_event', Event::STATUS_DITUTUP)
            ->update([
                'status_event' => Event::STATUS_DIARSIPKAN,
                'tanggal_diarsipkan' => now(),
            ]);

        Inertia::flash('toast', $berhasil
            ? ['type' => 'success', 'message' => "Event \"{$event->nama_event}\" berhasil diarsipkan dan dapat dilihat di tab Arsip."]
            : ['type' => 'error', 'message' => 'Hanya event yang sudah ditutup EO yang dapat diarsipkan.']);

        return back();
    }

    private function periode(Event $event): string
    {
        $mulai = FormatTanggal::ringkas($event->tanggal_mulai);

        return $event->tanggal_selesai->isSameDay($event->tanggal_mulai)
            ? $mulai
            : $mulai.' - '.FormatTanggal::ringkas($event->tanggal_selesai);
    }

    private function statusLabel(Event $event): string
    {
        return match ($event->status_event) {
            Event::STATUS_DRAFT => 'Draft',
            Event::STATUS_MENUNGGU_VERIFIKASI => 'Menunggu Verifikasi',
            Event::STATUS_DIPUBLIKASIKAN => 'Terverifikasi',
            Event::STATUS_DITUTUP => 'Ditutup',
            Event::STATUS_DIARSIPKAN => 'Diarsipkan',
            default => ucwords(str_replace('_', ' ', (string) $event->status_event)),
        };
    }

    private function statusEoLabel(?Eo $eo): string
    {
        return match ($eo?->status_verifikasi) {
            Eo::STATUS_DISETUJUI => 'Disetujui',
            Eo::STATUS_DITOLAK => 'Ditolak',
            Eo::STATUS_MENUNGGU => 'Menunggu verifikasi',
            default => 'Tidak diketahui',
        };
    }

    // Dokumen harus benar-benar milik event pada URL agar tidak bocor lintas event.
    private function pastikanMilikEvent(Event $event, DokumenEvent $dokumen): void
    {
        abort_unless((int) $dokumen->event_id === (int) $event->event_id, 404);
    }
}

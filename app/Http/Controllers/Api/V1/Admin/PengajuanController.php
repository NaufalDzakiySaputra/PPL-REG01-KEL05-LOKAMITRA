<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DocumentReviewRequest;
use App\Http\Requests\Admin\PengajuanRejectRequest;
use App\Http\Requests\Admin\PengajuanVerifikasiRequest;
use App\Models\DokumenPersyaratan;
use App\Models\User;
use App\Services\PengajuanService;
use App\Services\VerifikasiPendaftaranService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PengajuanController extends Controller
{
    public function __construct(
        private readonly PengajuanService $pengajuan,
        private readonly VerifikasiPendaftaranService $verifikasi
    ) {}

    /** Daftar pengajuan beserta ringkasan dan filter. */
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->pengajuan->daftar($request->query()));
    }

    /**
     * Keputusan verifikasi sesuai kontrak spec: verified atau rejected.
     * Endpoint lama (/approve, /reject) tetap dipertahankan.
     */
    public function perbaruiStatus(
        PengajuanVerifikasiRequest $request,
        User $pengajuan
    ): JsonResponse {
        $data = $request->validated();

        if ($data['status'] === 'verified') {
            $this->verifikasi->setujuiAkun($pengajuan, $request->user());
        } else {
            $this->verifikasi->tolakAkun(
                $pengajuan,
                $request->user(),
                $data['catatan']
            );
        }

        return response()->json([
            'message' => 'Status verifikasi akun mitra berhasil diperbarui.',
            'data' => [
                'id' => $pengajuan->id,
                'status_verifikasi' => $data['status'],
            ],
        ]);
    }

    /** Detail pengajuan, dokumen, dan kendala persetujuan. */
    public function show(User $pengajuan): JsonResponse
    {
        return response()->json($this->pengajuan->detail($pengajuan));
    }

    /** Menyetujui akun setelah seluruh syarat terpenuhi. */
    public function approve(Request $request, User $pengajuan): JsonResponse
    {
        $this->verifikasi->setujuiAkun($pengajuan, $request->user());

        return response()->json([
            'message' => 'Akun pendaftar berhasil disetujui dan sekarang dapat login.',
            'data' => ['status_akun' => User::STATUS_AKTIF],
        ]);
    }

    /** Menolak pengajuan dengan alasan (alias: catatan). */
    public function reject(PengajuanRejectRequest $request, User $pengajuan): JsonResponse
    {
        $this->verifikasi->tolakAkun(
            $pengajuan,
            $request->user(),
            $request->validated('alasan')
        );

        return response()->json([
            'message' => 'Pengajuan berhasil ditolak. Pendaftar akan menerima notifikasi.',
            'data' => ['status_akun' => User::STATUS_DITOLAK],
        ]);
    }

    /** Menyimpan hasil pemeriksaan satu dokumen. */
    public function reviewDocument(
        DocumentReviewRequest $request,
        User $pengajuan,
        DokumenPersyaratan $dokumen
    ): JsonResponse {
        $data = $request->validated();

        $this->verifikasi->periksaDokumen(
            $pengajuan,
            $dokumen,
            $request->user(),
            $data['status_pemeriksaan'],
            $data['catatan_admin'] ?? null
        );

        return response()->json([
            'message' => 'Hasil pemeriksaan dokumen berhasil disimpan.',
            'data' => [
                'id' => $dokumen->dokumen_id,
                'status' => $data['status_pemeriksaan'],
                'catatan_admin' => $data['catatan_admin'] ?? null,
            ],
        ]);
    }

    /** Menampilkan PDF atau gambar dari storage privat. */
    public function previewDocument(User $pengajuan, DokumenPersyaratan $dokumen): BinaryFileResponse
    {
        abort_unless((int) $dokumen->akun_id === (int) $pengajuan->id, 404);

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

    /** Mengunduh dokumen privat milik pengajuan yang bersangkutan. */
    public function downloadDocument(User $pengajuan, DokumenPersyaratan $dokumen): StreamedResponse
    {
        abort_unless((int) $dokumen->akun_id === (int) $pengajuan->id, 404);
        abort_unless(Storage::disk('local')->exists($dokumen->lokasi_file), 404);

        return Storage::disk('local')->download(
            $dokumen->lokasi_file,
            basename($dokumen->nama_file)
        );
    }
}

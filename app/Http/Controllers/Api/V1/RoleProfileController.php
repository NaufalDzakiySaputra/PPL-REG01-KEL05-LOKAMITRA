<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\DokumenUploadRequest;
use App\Http\Requests\Settings\RoleProfileUpdateRequest;
use App\Http\Resources\RoleProfileResource;
use App\Models\DokumenPersyaratan;
use App\Services\PendaftaranService;
use App\Services\RoleProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RoleProfileController extends Controller
{
    // Mengambil definisi dan nilai profil peran milik user.
    public function show(Request $request, RoleProfileService $service): RoleProfileResource
    {
        $user = $request->user();

        return RoleProfileResource::make(
            $user,
            $service->fieldsForForm($user)
        );
    }

    // Memperbarui field profil peran yang boleh diubah pemilik akun.
    public function update(RoleProfileUpdateRequest $request, RoleProfileService $service): RoleProfileResource
    {
        $user = $request->user();

        $service->update($user, $request->validated());

        return RoleProfileResource::make(
            $user,
            $service->fieldsForForm($user)
        )->additional([
            'message' => 'Profil peran berhasil diperbarui.',
        ]);
    }

    /** Mengunggah dokumen persyaratan tambahan untuk keperluan verifikasi. */
    public function storeDokumen(
        DokumenUploadRequest $request,
        PendaftaranService $pendaftaran
    ): JsonResponse {
        $data = $request->validated();

        $dokumen = $pendaftaran->simpanDokumen(
            $request->user(),
            $data['jenis_dokumen'],
            $data['file_dokumen']
        );

        return response()->json([
            'message' => 'Dokumen persyaratan berhasil diunggah.',
            'data' => [
                'id' => $dokumen->dokumen_id,
                'jenis_dokumen' => $dokumen->jenis_dokumen,
                // File privat: tautan unduh tetap melalui API terautentikasi.
                'file_url' => route(
                    'api.v1.role-profile.dokumen.download',
                    $dokumen,
                    false
                ),
            ],
        ], 201);
    }

    /** Mengunduh dokumen persyaratan milik akun yang sedang login. */
    public function downloadDokumen(
        Request $request,
        DokumenPersyaratan $dokumen
    ): StreamedResponse {
        abort_unless(
            (int) $dokumen->akun_id === (int) $request->user()->id,
            404
        );
        abort_unless(Storage::disk('local')->exists($dokumen->lokasi_file), 404);

        return Storage::disk('local')->download(
            $dokumen->lokasi_file,
            basename($dokumen->nama_file)
        );
    }
}

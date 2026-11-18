<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\PendaftaranService;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __construct(
        private readonly PendaftaranService $pendaftaran
    ) {}

    /**
     * Mendaftarkan akun baru beserta profil peran dan dokumen persyaratan.
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        $akun = $this->pendaftaran->daftarkan($request->validated());

        $pesan = $akun->role === User::ROLE_PENGUNJUNG
            ? 'Registrasi berhasil. Akun Anda sudah aktif, silakan login.'
            : 'Registrasi berhasil. Menunggu verifikasi Admin.';

        // Halaman login tetap menampilkan pesan status setelah pengalihan.
        if ($request->hasSession()) {
            $request->session()->flash('status', $pesan);
        }

        return response()->json([
            'message' => $pesan,
            'data' => UserResource::make($akun)->resolve($request),
        ], 201);
    }
}

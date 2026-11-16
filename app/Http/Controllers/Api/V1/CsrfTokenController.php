<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CsrfTokenController extends Controller
{
    /**
     * Token CSRF sesi aktif. Dipakai klien fetch untuk mencoba ulang request
     * yang gagal dengan 419 tanpa perlu memuat ulang halaman.
     */
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'token' => $request->session()->token(),
        ]);
    }
}

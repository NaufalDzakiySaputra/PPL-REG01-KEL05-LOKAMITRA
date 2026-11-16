<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        // kalau request berupa JSON
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        // membersihkan riwayat halaman Inertia
        Inertia::clearHistory();

        // arahkan kembali ke halaman login
        return redirect()
            ->route('login')
            ->with('status', 'Anda telah keluar dari sistem.');
    }
}
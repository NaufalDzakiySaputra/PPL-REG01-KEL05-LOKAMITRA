<?php

namespace App\Http\Responses;

use Inertia\Inertia;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * Response logout untuk API JSON dan halaman web.
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Logout berhasil.',
            ]);
        }

        // membersihkan riwayat halaman Inertia
        Inertia::clearHistory();

        // arahkan kembali ke halaman login
        return redirect()
            ->route('login')
            ->with('status', 'Anda telah keluar dari sistem.');
    }
}

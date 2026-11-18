<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse as SuccessfulPasswordResetLinkRequestResponseContract;

class SentPasswordResetLinkResponse implements SuccessfulPasswordResetLinkRequestResponseContract
{
    /**
     * Response permintaan tautan reset kata sandi.
     */
    public function toResponse($request)
    {
        $pesan = 'Tautan reset kata sandi telah dikirim ke email Anda.';

        if ($request->wantsJson()) {
            return new JsonResponse(['message' => $pesan], 200);
        }

        return back()->with('status', $pesan);
    }
}

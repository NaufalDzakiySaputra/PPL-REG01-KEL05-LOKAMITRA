<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\PasswordResetResponse as PasswordResetResponseContract;

class PasswordResetSuccessResponse implements PasswordResetResponseContract
{
    /**
     * Response setelah kata sandi berhasil diperbarui.
     */
    public function toResponse($request)
    {
        $pesan = 'Kata sandi berhasil diperbarui.';

        if ($request->wantsJson()) {
            // Halaman login menampilkan pesan ini setelah pengguna diarahkan.
            if ($request->hasSession()) {
                $request->session()->flash('status', $pesan);
            }

            return new JsonResponse(['message' => $pesan], 200);
        }

        return redirect()
            ->route('login')
            ->with('status', $pesan);
    }
}

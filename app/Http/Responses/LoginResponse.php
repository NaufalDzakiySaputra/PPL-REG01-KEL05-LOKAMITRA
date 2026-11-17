<?php

namespace App\Http\Responses;

use App\Http\Resources\UserResource;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Response login untuk API JSON dan halaman web.
     */
    public function toResponse($request)
    {
        $user = $request->user();
        $routeName = $user?->dashboardRouteName();

        // Akun dengan role yang tidak dikenal tidak boleh masuk ke dashboard.
        abort_if($routeName === null, 403, 'Role akun tidak dikenali.');

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Login berhasil.',
                'data' => UserResource::make($user)->resolve($request),
                'redirect' => route($routeName),
            ]);
        }

        // Setiap role diarahkan ke dashboard masing-masing.
        return redirect()->route($routeName);
    }
}

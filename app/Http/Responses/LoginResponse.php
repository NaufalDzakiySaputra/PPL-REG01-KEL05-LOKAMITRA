<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();
        $routeName = $user?->dashboardRouteName();

        // Akun dengan role yang tidak dikenal tidak boleh masuk ke dashboard.
        abort_if($routeName === null, 403, 'Role akun tidak dikenali.');

        if ($request->wantsJson()) {
            return response()->json([
                'redirect' => route($routeName),
            ]);
        }

        // Setiap role diarahkan ke dashboard masing-masing.
        return redirect()->route($routeName);
    }
}

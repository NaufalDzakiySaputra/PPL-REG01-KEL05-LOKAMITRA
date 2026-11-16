<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        $adalahApi = $request->is('api/*');

        // Route ini seharusnya juga dilindungi middleware auth.
        if (! $user) {
            return $adalahApi
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->guest(route('login'));
        }

        // Jika status akun berubah saat sesi masih aktif, akhiri sesi tersebut.
        if ($user->status_akun !== User::STATUS_AKTIF) {
            $pesan = match ($user->status_akun) {
                User::STATUS_MENUNGGU => 'Akun Anda masih menunggu verifikasi Admin.',
                User::STATUS_DITOLAK => 'Akun Anda ditolak dan tidak dapat mengakses sistem.',
                default => 'Akun Anda tidak aktif. Silakan hubungi Admin.',
            };

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $adalahApi
                ? response()->json(['message' => $pesan, 'code' => 'account_inactive'], 403)
                : redirect()->route('login')->withErrors(['email' => $pesan]);
        }

        // Role yang tidak sesuai tidak boleh membuka dashboard role lain.
        if (! in_array($user->role, $roles, true)) {
            return $adalahApi
                ? response()->json(['message' => 'Anda tidak memiliki akses ke halaman ini.'], 403)
                : abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}

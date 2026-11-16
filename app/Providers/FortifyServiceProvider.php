<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    // Daftarkan response login dan logout khusus Lokamitra.
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
    }

    public function boot(): void
    {
        $this->configureActions();
        $this->configureAuthentication();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
    }

    // Hanya akun aktif yang boleh login.
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            $kolomLogin = Fortify::username();
            $email = Str::lower(trim((string) $request->input($kolomLogin)));
            $password = (string) $request->input('password');

            $user = User::query()->where($kolomLogin, $email)->first();

            if (!$user || !Hash::check($password, $user->password)) {
                return null;
            }

            $pesanStatus = match ($user->status_akun) {
                User::STATUS_AKTIF => null,
                User::STATUS_MENUNGGU => 'Akun Anda masih menunggu verifikasi Admin.',
                User::STATUS_DITOLAK => 'Pendaftaran akun Anda ditolak. Silakan hubungi Admin.',
                default => 'Akun Anda belum dapat digunakan. Silakan hubungi Admin.',
            };

            if ($pesanStatus !== null) {
                throw ValidationException::withMessages([
                    $kolomLogin => $pesanStatus,
                ]);
            }

            return $user;
        });
    }

    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        // Halaman registrasi ditangani RegisterController, bukan Fortify.
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $kolomLogin = Fortify::username();
            $throttleKey = Str::transliterate(
                Str::lower((string) $request->input($kolomLogin)).'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}

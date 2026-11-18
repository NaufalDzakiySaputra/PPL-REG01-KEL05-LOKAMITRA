<?php

use App\Http\Controllers\Api\V1\Admin\PengajuanController as AdminPengajuanController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\CsrfTokenController;
use App\Http\Controllers\Api\V1\Eo\EventController as EoEventController;
use App\Http\Controllers\Api\V1\PasswordController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RoleProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Lokamitra
|--------------------------------------------------------------------------
|
| API ini dipakai oleh frontend web yang sama. Karena itu route memakai
| middleware "web" agar session login dan proteksi CSRF tetap aktif.
|
*/

Route::prefix('v1')->middleware('web')->group(function () {
    // Token CSRF untuk klien fetch; tidak perlu login agar sesi baru tetap dapat token.
    Route::get('/csrf-token', CsrfTokenController::class)
        ->name('api.v1.csrf-token');

    // Login dan logout memakai pipeline serta guard dari Laravel Fortify.
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware(['guest:web', 'throttle:login'])
        ->name('api.v1.login');

    Route::post('/logout', [LogoutController::class, 'destroy'])
        ->middleware('auth:web')
        ->name('api.v1.logout');

    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('guest:web')
        ->name('api.v1.register');

    // Lupa dan reset kata sandi memakai broker serta action bawaan Fortify.
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('guest:web')
        ->name('api.v1.forgot-password');

    Route::post('/reset-password', [ResetPasswordController::class, 'store'])
        ->middleware('guest:web')
        ->name('api.v1.reset-password');

    Route::middleware('auth')->group(function () {
        Route::get('/me', [ProfileController::class, 'show'])
            ->name('api.v1.me.show');

        Route::patch('/me', [ProfileController::class, 'update'])
            ->name('api.v1.me.update');

        Route::put('/me/password', [PasswordController::class, 'update'])
            ->middleware('throttle:6,1')
            ->name('api.v1.me.password.update');

        Route::delete('/profile', [ProfileController::class, 'destroy'])
            ->name('api.v1.profile.destroy');

        Route::middleware('role:eo,vendor,pemda,komunitas,akademisi,media')->group(function () {
            Route::get('/me/role-profile', [RoleProfileController::class, 'show'])
                ->name('api.v1.me.role-profile.show');

            Route::put('/me/role-profile', [RoleProfileController::class, 'update'])
                ->name('api.v1.me.role-profile.update');

            Route::post('/role-profile/dokumen', [RoleProfileController::class, 'storeDokumen'])
                ->name('api.v1.role-profile.dokumen.store');

            Route::get('/role-profile/dokumen/{dokumen}/download', [RoleProfileController::class, 'downloadDokumen'])
                ->whereNumber('dokumen')
                ->name('api.v1.role-profile.dokumen.download');
        });

        // Modul Manajemen Event untuk Event Organizer.
        Route::middleware('role:eo')->prefix('eo')->name('api.v1.eo.')->group(function () {
            Route::post('/events', [EoEventController::class, 'store'])
                ->name('events.store');
        });

        // Verifikasi pendaftaran hanya untuk Admin. File dokumen tetap privat.
        Route::middleware('role:admin')->group(function () {
            Route::prefix('admin/pengajuan')
                ->name('api.v1.admin.pengajuan.')
                ->group(function () {
                    Route::get('/', [AdminPengajuanController::class, 'index'])
                        ->name('index');

                    Route::get('/{pengajuan}', [AdminPengajuanController::class, 'show'])
                        ->whereNumber('pengajuan')
                        ->name('show');

                    Route::patch('/{pengajuan}', [AdminPengajuanController::class, 'perbaruiStatus'])
                        ->whereNumber('pengajuan')
                        ->name('update');

                    Route::post('/{pengajuan}/approve', [AdminPengajuanController::class, 'approve'])
                        ->whereNumber('pengajuan')
                        ->name('approve');

                    Route::post('/{pengajuan}/reject', [AdminPengajuanController::class, 'reject'])
                        ->whereNumber('pengajuan')
                        ->name('reject');

                    Route::post('/{pengajuan}/documents/{dokumen}/review', [AdminPengajuanController::class, 'reviewDocument'])
                        ->whereNumber('pengajuan')
                        ->whereNumber('dokumen')
                        ->name('documents.review');

                    Route::get('/{pengajuan}/documents/{dokumen}/preview', [AdminPengajuanController::class, 'previewDocument'])
                        ->whereNumber('pengajuan')
                        ->whereNumber('dokumen')
                        ->name('documents.preview');

                    Route::get('/{pengajuan}/documents/{dokumen}/download', [AdminPengajuanController::class, 'downloadDocument'])
                        ->whereNumber('pengajuan')
                        ->whereNumber('dokumen')
                        ->name('documents.download');
                });
        });
    });
});

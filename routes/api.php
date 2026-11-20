<?php

use App\Http\Controllers\Api\V1\Admin\EventController as AdminEventController;
use App\Http\Controllers\Api\V1\Admin\PengajuanController as AdminPengajuanController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\CsrfTokenController;
use App\Http\Controllers\Api\V1\Eo\EventController as EoEventController;
use App\Http\Controllers\Api\V1\Eo\EventOptionController;
use App\Http\Controllers\Api\V1\Eo\InformasiPublikEventController;
use App\Http\Controllers\Api\V1\Eo\PartnershipRequestController;
use App\Http\Controllers\Api\V1\Eo\StakeholderController;
use App\Http\Controllers\Api\V1\PasswordController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\Publik\EventController as EventPublikController;
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
    Route::get('/csrf-token', CsrfTokenController::class)
        ->name('api.v1.csrf-token');

    // Informasi event publik (F-06 / PBI-035): dapat dibaca tanpa login.
    Route::get('/events', [EventPublikController::class, 'index'])
        ->name('api.v1.events.index');

    Route::get('/events/{event}', [EventPublikController::class, 'show'])
        ->whereNumber('event')
        ->name('api.v1.events.show');

    Route::post('/login', [LoginController::class, 'store'])
        ->middleware(['guest:web', 'throttle:login'])
        ->name('api.v1.login');

    Route::post('/logout', [LogoutController::class, 'destroy'])
        ->middleware('auth:web')
        ->name('api.v1.logout');

    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('guest:web')
        ->name('api.v1.register');

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

        // Modul Event dan Kolaborasi untuk Event Organizer.
        Route::middleware('role:eo')->prefix('eo')->name('api.v1.eo.')->group(function () {
            Route::get('/events', [EoEventController::class, 'index'])
                ->name('events.index');

            Route::post('/events', [EoEventController::class, 'store'])
                ->name('events.store');

            Route::get('/events/{event}', [EoEventController::class, 'show'])
                ->whereNumber('event')
                ->name('events.show');

            Route::put('/events/{event}', [EoEventController::class, 'update'])
                ->whereNumber('event')
                ->name('events.update');

            Route::post('/events/{event}/tutup', [EoEventController::class, 'close'])
                ->whereNumber('event')
                ->name('events.close');

            Route::get('/stakeholders', [StakeholderController::class, 'index'])
                ->name('stakeholders.index');

            Route::get('/stakeholders/{type}/{user}', [StakeholderController::class, 'show'])
                ->name('stakeholders.show');

            Route::get('/events/options', [EventOptionController::class, 'index'])
                ->name('events.options');

            Route::post('/partnership-requests', [PartnershipRequestController::class, 'store'])
                ->name('partnership-requests.store');

            // Informasi publik event (PBI-035): rundown, layout/dokumen, kontak darurat.
            Route::get('/events/{event}/informasi-publik', [InformasiPublikEventController::class, 'show'])
                ->whereNumber('event')
                ->name('events.informasi-publik.show');

            Route::post('/events/{event}/rundown', [InformasiPublikEventController::class, 'storeRundown'])
                ->whereNumber('event')
                ->name('events.rundown.store');

            Route::put('/events/{event}/rundown/{rundown}', [InformasiPublikEventController::class, 'updateRundown'])
                ->whereNumber(['event', 'rundown'])
                ->name('events.rundown.update');

            Route::delete('/events/{event}/rundown/{rundown}', [InformasiPublikEventController::class, 'destroyRundown'])
                ->whereNumber(['event', 'rundown'])
                ->name('events.rundown.destroy');

            Route::post('/events/{event}/dokumen', [InformasiPublikEventController::class, 'storeDokumen'])
                ->whereNumber('event')
                ->name('events.dokumen.store');

            Route::delete('/events/{event}/dokumen/{dokumen}', [InformasiPublikEventController::class, 'destroyDokumen'])
                ->whereNumber(['event', 'dokumen'])
                ->name('events.dokumen.destroy');

            Route::post('/events/{event}/kontak-darurat', [InformasiPublikEventController::class, 'storeKontak'])
                ->whereNumber('event')
                ->name('events.kontak-darurat.store');

            Route::put('/events/{event}/kontak-darurat/{kontak}', [InformasiPublikEventController::class, 'updateKontak'])
                ->whereNumber(['event', 'kontak'])
                ->name('events.kontak-darurat.update');

            Route::delete('/events/{event}/kontak-darurat/{kontak}', [InformasiPublikEventController::class, 'destroyKontak'])
                ->whereNumber(['event', 'kontak'])
                ->name('events.kontak-darurat.destroy');
        });

        // Modul Admin.
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

            Route::prefix('admin/events')
                ->name('api.v1.admin.events.')
                ->group(function () {
                    Route::get('/', [AdminEventController::class, 'index'])
                        ->name('index');

                    Route::get('/arsip', [AdminEventController::class, 'archives'])
                        ->name('archives');

                    Route::post('/{event}/verifikasi', [AdminEventController::class, 'verifikasi'])
                        ->whereNumber('event')
                        ->name('verifikasi');

                    Route::post('/{event}/arsip', [AdminEventController::class, 'archive'])
                        ->whereNumber('event')
                        ->name('archive');
                });
        });
    });
});

<?php

use App\Http\Controllers\Api\V1\Eo\StakeholderController;
use App\Http\Controllers\Api\V1\Eo\EventOptionController;
use App\Http\Controllers\Api\V1\Eo\PartnershipRequestController;
use App\Http\Controllers\Api\V1\CsrfTokenController;
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

    Route::middleware('auth')->group(function () {
        Route::get('/me', [ProfileController::class, 'show'])
            ->name('api.v1.me.show');

        Route::patch('/me', [ProfileController::class, 'update'])
            ->name('api.v1.me.update');

        Route::delete('/me', [ProfileController::class, 'destroy'])
            ->name('api.v1.me.destroy');

        Route::put('/me/password', [PasswordController::class, 'update'])
            ->middleware('throttle:6,1')
            ->name('api.v1.me.password.update');

        Route::middleware('role:eo,vendor,pemda,komunitas,akademisi,media')->group(function () {
            Route::get('/me/role-profile', [RoleProfileController::class, 'show'])
                ->name('api.v1.me.role-profile.show');

            Route::put('/me/role-profile', [RoleProfileController::class, 'update'])
                ->name('api.v1.me.role-profile.update');
        
        Route::middleware('role:eo')->prefix('eo')->group(function () {
            Route::get('/stakeholders', [StakeholderController::class, 'index'])
                ->name('api.v1.eo.stakeholders.index');

            Route::get('/stakeholders/{type}/{user}', [StakeholderController::class, 'show'])
                ->name('api.v1.eo.stakeholders.show');

            Route::get('/events/options', [EventOptionController::class, 'index'])
                ->name('api.v1.eo.events.options');
            
            Route::post('/partnership-requests', [PartnershipRequestController::class, 'store'])
                ->name('api.v1.eo.partnership-requests.store');
            });
        });
    });
});

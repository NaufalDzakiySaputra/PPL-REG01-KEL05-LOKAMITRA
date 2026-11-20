
<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\PengajuanController;
use App\Http\Controllers\Akademisi\DashboardController as AkademisiDashboardController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Eo\DashboardController as EoDashboardController;
use App\Http\Controllers\Eo\EventController as EoEventController;
use App\Http\Controllers\Eo\StakeholderController as EoStakeholderController;
use App\Http\Controllers\Komunitas\DashboardController as KomunitasDashboardController;
use App\Http\Controllers\Media\DashboardController as MediaDashboardController;
use App\Http\Controllers\Pemda\DashboardController as PemdaDashboardController;
use App\Http\Controllers\Pengunjung\DashboardController as PengunjungDashboardController;
use App\Http\Controllers\Pengunjung\EventPublikController;
use App\Http\Controllers\Settings\RoleProfileController;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboardController;
use App\Http\Middleware\PreventBackHistory;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController as FortifyAuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController;
use Laravel\Fortify\Http\Controllers\ConfirmedPasswordStatusController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;

// Landing page: katalog event publik (F-06) ditampilkan di root.
Route::get('/', [EventPublikController::class, 'index'])->name('home');

// Informasi event publik (F-06): daftar event terverifikasi tanpa login.
Route::get('/events', [EventPublikController::class, 'index'])->name('events.index');
Route::get('/events/{event}', [EventPublikController::class, 'show'])
    ->name('events.show')
    ->whereNumber('event');
Route::get('/events/{event}/dokumen/{dokumen}', [EventPublikController::class, 'unduhDokumen'])
    ->name('events.dokumen')
    ->whereNumber(['event', 'dokumen']);

// Halaman autentikasi Fortify. Transaksi login/logout sudah pindah ke /api/v1.
Route::middleware('guest:web')->group(function () {
    Route::get('/login', [FortifyAuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
});

// Route bawaan Fortify yang belum termasuk lingkup migrasi.
Route::middleware('auth:web')->group(function () {
    Route::get('/user/confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('/user/confirm-password', [ConfirmablePasswordController::class, 'store'])
        ->name('password.confirm.store');

    Route::get('/user/confirmed-password-status', [ConfirmedPasswordStatusController::class, 'show'])
        ->name('password.confirmation');
});

// Registrasi publik Lokamitra menggunakan validasi dan pembuatan profil custom.
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
});

// Semua dashboard wajib login dan tidak disimpan dalam cache browser.
Route::middleware(['auth', PreventBackHistory::class])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/admin/dashboard', AdminDashboardController::class)
        ->middleware('role:admin')
        ->name('admin.dashboard');

    // Pengelolaan dan verifikasi pendaftaran hanya dapat diakses Admin.
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/pengajuan', [PengajuanController::class, 'index'])
            ->name('pengajuan.index');

        Route::get('/pengajuan/{pengajuan}', [PengajuanController::class, 'show'])
            ->whereNumber('pengajuan')
            ->name('pengajuan.show');

        Route::get('/events', [AdminEventController::class, 'index'])
            ->name('events.index');

        // Pemeriksaan informasi publik event sebelum/sesudah diverifikasi.
        Route::get('/events/{event}', [AdminEventController::class, 'show'])
            ->whereNumber('event')
            ->name('events.show');

        Route::get('/events/{event}/dokumen/{dokumen}', [AdminEventController::class, 'unduhDokumen'])
            ->whereNumber(['event', 'dokumen'])
            ->name('events.dokumen.unduh');

        Route::get('/events/{event}/dokumen/{dokumen}/pratinjau', [AdminEventController::class, 'pratinjauDokumen'])
            ->whereNumber(['event', 'dokumen'])
            ->name('events.dokumen.pratinjau');

        Route::post('/events/{event}/arsipkan', [AdminEventController::class, 'arsipkan'])
            ->whereNumber('event')
            ->name('events.archive');

    });

    Route::get('/eo/dashboard', EoDashboardController::class)
        ->middleware('role:eo')
        ->name('eo.dashboard');

    Route::prefix('eo')->name('eo.')->middleware('role:eo')->group(function () {
        Route::get('/events', [EoEventController::class, 'index'])->name('events.index');
        Route::get('/events/buat', [EoEventController::class, 'create'])->name('events.create');
        Route::get('/events/{event}', [EoEventController::class, 'show'])->whereNumber('event')->name('events.show');
        Route::get('/events/{event}/ubah', [EoEventController::class, 'edit'])->whereNumber('event')->name('events.edit');

        // Direktori stakeholder
        Route::get('/stakeholders', [EoStakeholderController::class, 'index'])
            ->name('stakeholders.index');

        // Detail stakeholder
        Route::get('/stakeholders/{type}/{user}', [EoStakeholderController::class, 'show'])
            ->whereIn('type', ['pemda', 'vendor', 'komunitas', 'akademisi', 'media'])
            ->whereNumber('user')
            ->name('stakeholders.show');
    });

    Route::get('/vendor/dashboard', VendorDashboardController::class)
        ->middleware('role:vendor')
        ->name('vendor.dashboard');

    Route::get('/pemda/dashboard', PemdaDashboardController::class)
        ->middleware('role:pemda')
        ->name('pemda.dashboard');

    Route::get('/komunitas/dashboard', KomunitasDashboardController::class)
        ->middleware('role:komunitas')
        ->name('komunitas.dashboard');

    Route::get('/akademisi/dashboard', AkademisiDashboardController::class)
        ->middleware('role:akademisi')
        ->name('akademisi.dashboard');

    Route::get('/media/dashboard', MediaDashboardController::class)
        ->middleware('role:media')
        ->name('media.dashboard');

    Route::get('/pengunjung/dashboard', PengunjungDashboardController::class)
        ->middleware('role:pengunjung')
        ->name('pengunjung.dashboard');

    // Profil organisasi hanya dapat diubah oleh akun mitra dengan status aktif.
    Route::middleware('role:eo,vendor,pemda,komunitas,akademisi,media')->group(function () {
        Route::get('/profil-peran', [RoleProfileController::class, 'edit'])
            ->name('role-profile.edit');
    });
});

require __DIR__.'/settings.php';

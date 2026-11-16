
<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
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
use App\Http\Controllers\Settings\RoleProfileController;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboardController;
use App\Http\Middleware\PreventBackHistory;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Registrasi publik Lokamitra menggunakan validasi dan pembuatan profil custom.
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
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

        Route::post('/pengajuan/{pengajuan}/setujui', [PengajuanController::class, 'approve'])
            ->whereNumber('pengajuan')
            ->name('pengajuan.approve');

        Route::post('/pengajuan/{pengajuan}/tolak', [PengajuanController::class, 'reject'])
            ->whereNumber('pengajuan')
            ->name('pengajuan.reject');

        Route::post('/pengajuan/{pengajuan}/dokumen/{dokumen}/periksa', [PengajuanController::class, 'reviewDocument'])
            ->whereNumber('pengajuan')
            ->whereNumber('dokumen')
            ->name('pengajuan.documents.review');

        Route::get('/pengajuan/{pengajuan}/dokumen/{dokumen}/lihat', [PengajuanController::class, 'previewDocument'])
            ->whereNumber('pengajuan')
            ->whereNumber('dokumen')
            ->name('pengajuan.documents.preview');

        Route::get('/pengajuan/{pengajuan}/dokumen/{dokumen}/unduh', [PengajuanController::class, 'downloadDocument'])
            ->whereNumber('pengajuan')
            ->whereNumber('dokumen')
            ->name('pengajuan.documents.download');
    });

    Route::get('/eo/dashboard', EoDashboardController::class)
        ->middleware('role:eo')
        ->name('eo.dashboard');

    Route::prefix('eo')->name('eo.')->middleware('role:eo')->group(function () {
        // Event
        Route::get('/events', [EoEventController::class, 'index'])->name('events.index');
        Route::get('/events/buat', [EoEventController::class, 'create'])->name('events.create');
        Route::post('/events', [EoEventController::class, 'store'])->name('events.store');
        Route::get('/events/{event}', [EoEventController::class, 'show'])->whereNumber('event')->name('events.show');
        Route::get('/events/{event}/ubah', [EoEventController::class, 'edit'])->whereNumber('event')->name('events.edit');
        Route::put('/events/{event}', [EoEventController::class, 'update'])->whereNumber('event')->name('events.update');

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

        Route::put('/profil-peran', [RoleProfileController::class, 'update'])
            ->name('role-profile.update');
    });
});

require __DIR__.'/settings.php';

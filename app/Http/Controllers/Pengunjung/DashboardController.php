<?php

namespace App\Http\Controllers\Pengunjung;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\PengunjungDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    // Beranda Pengunjung: katalog event terverifikasi dengan pencarian dan filter.
    public function __invoke(Request $request, PengunjungDashboardService $katalog): Response
    {
        return Inertia::render('Pengunjung/Dashboard', $katalog->katalog(
            $request->only(['q', 'kategori', 'kota', 'urut'])
        ));
    }
}

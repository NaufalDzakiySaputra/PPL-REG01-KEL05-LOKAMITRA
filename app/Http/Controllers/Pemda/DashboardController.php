<?php

namespace App\Http\Controllers\Pemda;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\MitraDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    // Dashboard Pemda: tugas, event yang diikuti, dan event yang tersedia.
    public function __invoke(Request $request, MitraDashboardService $dashboard): Response
    {
        return Inertia::render('pemda/Dashboard', $dashboard->data($request->user()));
    }
}

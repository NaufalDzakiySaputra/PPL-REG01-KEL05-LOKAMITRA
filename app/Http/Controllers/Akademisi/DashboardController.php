<?php

namespace App\Http\Controllers\Akademisi;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\MitraDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    // Dashboard Akademisi: tugas, event yang diikuti, dan event yang tersedia.
    public function __invoke(Request $request, MitraDashboardService $dashboard): Response
    {
        return Inertia::render('akademisi/Dashboard', $dashboard->data($request->user()));
    }
}

<?php

namespace App\Http\Controllers\Eo;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\EoDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    // Dashboard EO: progres persiapan event aktif.
    public function __invoke(Request $request, EoDashboardService $dashboard): Response
    {
        return Inertia::render('eo/Dashboard', $dashboard->data($request->user()));
    }
}

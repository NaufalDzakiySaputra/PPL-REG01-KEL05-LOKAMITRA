<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\AdminDashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    // Dashboard Admin: ringkasan platform, antrean verifikasi, dan aktivitas.
    public function __invoke(AdminDashboardService $dashboard): Response
    {
        return Inertia::render('admin/Dashboard', $dashboard->data());
    }
}

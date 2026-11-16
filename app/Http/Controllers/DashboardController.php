<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    // Route /dashboard hanya bertugas mengarahkan user ke dashboard rolenya.
    public function __invoke(Request $request): RedirectResponse
    {
        $routeName = $request->user()->dashboardRouteName();

        abort_if($routeName === null, 403, 'Role akun tidak dikenali.');

        return redirect()->route($routeName);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class RoleProfileController extends Controller
{
    // Halaman hanya merender shell; data profil diambil lewat REST API.
    public function edit(): Response
    {
        return Inertia::render('settings/RoleProfile');
    }
}

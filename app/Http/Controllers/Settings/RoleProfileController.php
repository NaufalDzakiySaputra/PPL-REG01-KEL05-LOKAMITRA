<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\RoleProfileUpdateRequest;
use App\Services\RoleProfileService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RoleProfileController extends Controller
{
    // Halaman hanya merender shell; data profil diambil lewat REST API.
    public function edit(): Response
    {
        return Inertia::render('settings/RoleProfile');
    }

    // Memperbarui field profil yang memang boleh diedit oleh pemilik akun.
    public function update(RoleProfileUpdateRequest $request, RoleProfileService $service): RedirectResponse
    {
        $service->update($request->user(), $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Profil peran berhasil diperbarui.',
        ]);

        return to_route('role-profile.edit');
    }
}

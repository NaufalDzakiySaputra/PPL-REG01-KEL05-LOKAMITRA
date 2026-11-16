<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\RoleProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleProfileController extends Controller
{
    // Menampilkan profil organisasi atau profil peran milik akun aktif.
    public function edit(Request $request, RoleProfileService $service): Response
    {
        return Inertia::render('settings/RoleProfile', [
            'roleLabel' => $service->roleLabel($request->user()),
            'fields' => $service->fieldsForForm($request->user()),
        ]);
    }

    // Memperbarui field profil yang memang boleh diedit oleh pemilik akun.
    public function update(Request $request, RoleProfileService $service): RedirectResponse
    {
        $data = $request->validate(
            $service->rules($request->user()),
            [
                'required' => ':attribute wajib diisi.',
                'url' => ':attribute harus berupa URL yang valid.',
                'integer' => ':attribute harus berupa angka.',
                'min' => ':attribute minimal :min.',
            ],
            collect($service->definitions($request->user()))
                ->mapWithKeys(fn (array $field, string $key) => [$key => $field['label']])
                ->all()
        );

        $service->update($request->user(), $data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Profil peran berhasil diperbarui.',
        ]);

        return to_route('role-profile.edit');
    }
}

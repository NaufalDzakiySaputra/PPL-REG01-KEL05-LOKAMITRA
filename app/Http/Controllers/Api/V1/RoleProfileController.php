<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\RoleProfileUpdateRequest;
use App\Http\Resources\RoleProfileResource;
use App\Services\RoleProfileService;
use Illuminate\Http\Request;

class RoleProfileController extends Controller
{
    // Mengambil definisi dan nilai profil peran milik user.
    public function show(Request $request, RoleProfileService $service): RoleProfileResource
    {
        $user = $request->user();

        return RoleProfileResource::make(
            $user,
            $service->fieldsForForm($user)
        );
    }

    // Memperbarui field profil peran yang boleh diubah pemilik akun.
    public function update(RoleProfileUpdateRequest $request, RoleProfileService $service): RoleProfileResource
    {
        $user = $request->user();

        $service->update($user, $request->validated());

        return RoleProfileResource::make(
            $user,
            $service->fieldsForForm($user)
        )->additional([
            'message' => 'Profil peran berhasil diperbarui.',
        ]);
    }
}

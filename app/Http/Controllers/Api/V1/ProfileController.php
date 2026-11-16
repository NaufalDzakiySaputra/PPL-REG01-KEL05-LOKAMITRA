<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    // Mengambil data akun user yang sedang login.
    public function show(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }

    // Memperbarui nama dan email akun.
    public function update(ProfileUpdateRequest $request): UserResource
    {
        $user = $request->user();

        $user->fill($request->validated());

        // Email baru harus diverifikasi ulang.
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return UserResource::make($user)->additional([
            'message' => 'Profil berhasil diperbarui.',
        ]);
    }

    // Menghapus akun beserta seluruh data yang terhubung.
    public function destroy(ProfileDeleteRequest $request): JsonResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(null, 204);
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    // Menampilkan formulir registrasi dan pilihan peran.
    public function create(Request $request): Response
    {
        $peran = config('lokamitra.peran_registrasi', []);
        $roleDiminta = $request->string('role')->toString();

        return Inertia::render('auth/Register', [
            'roles' => collect($peran)
                ->map(fn (string $label, string $value) => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values(),
            'defaultRole' => array_key_exists($roleDiminta, $peran)
                ? $roleDiminta
                : array_key_first($peran),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'roleInfo' => config('lokamitra.registrasi_info', []),
            'documentRequirements' => config('lokamitra.dokumen_registrasi', []),
        ]);
    }
}

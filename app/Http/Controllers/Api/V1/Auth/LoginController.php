<?php

namespace App\Http\Controllers\Api\V1\Auth;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Requests\LoginRequest;

class LoginController extends AuthenticatedSessionController
{
    /**
     * Autentikasi pengguna memakai pipeline Fortify, lalu balas dengan JSON.
     *
     * Response dibuat oleh App\Http\Responses\LoginResponse agar logika
     * pengalihan dashboard tetap satu sumber.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        return $this->loginPipeline($request)->then(
            fn (LoginRequest $request) => app(LoginResponseContract::class)->toResponse($request)
        );
    }
}

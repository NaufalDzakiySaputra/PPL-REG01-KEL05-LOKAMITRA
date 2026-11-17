<?php

namespace App\Http\Controllers\Api\V1\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

class LogoutController
{
    public function __construct(
        private readonly AuthenticatedSessionController $fortify
    ) {}

    /**
     * Akhiri sesi login lalu balas dengan JSON.
     *
     * Proses logout tetap memakai implementasi Fortify; bentuk response
     * diatur oleh App\Http\Responses\LogoutResponse.
     */
    public function destroy(Request $request): JsonResponse
    {
        return $this->fortify->destroy($request)->toResponse($request);
    }
}

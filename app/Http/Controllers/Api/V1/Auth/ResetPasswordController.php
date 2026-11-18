<?php

namespace App\Http\Controllers\Api\V1\Auth;

use Laravel\Fortify\Http\Controllers\NewPasswordController;

class ResetPasswordController extends NewPasswordController
{
    /*
     * Memakai store() bawaan Fortify: validasi token, ResetUserPassword, dan
     * CompletePasswordReset. Bentuk response JSON diatur oleh
     * App\Http\Responses\PasswordResetSuccessResponse.
     */
}

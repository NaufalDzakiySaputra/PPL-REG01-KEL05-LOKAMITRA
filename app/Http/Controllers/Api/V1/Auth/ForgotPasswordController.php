<?php

namespace App\Http\Controllers\Api\V1\Auth;

use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;

class ForgotPasswordController extends PasswordResetLinkController
{
    /*
     * Memakai store() bawaan Fortify: validasi email, broker reset password,
     * dan pengiriman notifikasi. Bentuk response JSON diatur oleh
     * App\Http\Responses\SentPasswordResetLinkResponse.
     */
}

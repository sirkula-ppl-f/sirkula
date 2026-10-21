<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;

/**
 * Respons permintaan tautan reset kata sandi, dipakai untuk hasil sukses maupun gagal.
 *
 * Satu kelas untuk kedua kasus supaya respons selalu identik dan tidak mengungkap
 * apakah sebuah email terdaftar (FR-002).
 */
class PasswordResetLinkResponse implements FailedPasswordResetLinkRequestResponse, SuccessfulPasswordResetLinkRequestResponse
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): RedirectResponse
    {
        return redirect()->route('password.request')->with('status', __('passwords.sent'));
    }
}

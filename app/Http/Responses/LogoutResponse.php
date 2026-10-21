<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * Arahkan ke halaman login dengan pesan berhasil keluar.
     *
     * Logout, invalidate sesi, dan regenerasi token CSRF sudah dilakukan oleh
     * AuthenticatedSessionController::destroy milik Fortify sebelum response ini.
     *
     * @param  Request  $request
     */
    public function toResponse($request): RedirectResponse
    {
        return redirect()->route('login')->with('status', __('auth.logout'));
    }
}

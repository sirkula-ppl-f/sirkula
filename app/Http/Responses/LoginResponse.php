<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Arahkan user sesuai role: admin ke /admin, selain itu ke /dashboard.
     *
     * @param  Request  $request
     */
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        // Warga yang sempat membuka /admin sebelum login tidak perlu dibawa ke halaman 403.
        if (! $user->isAdmin()) {
            $intendedPath = trim((string) parse_url((string) $request->session()->get('url.intended'), PHP_URL_PATH), '/');

            if (Str::is(['admin', 'admin/*'], $intendedPath)) {
                $request->session()->forget('url.intended');
            }
        }

        return redirect()->intended(
            $user->isAdmin() ? route('admin.dashboard') : route('dashboard')
        );
    }
}

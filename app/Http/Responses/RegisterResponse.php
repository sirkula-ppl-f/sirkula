<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * Arahkan warga yang baru mendaftar ke dashboard.
     *
     * @param  Request  $request
     */
    public function toResponse($request): RedirectResponse
    {
        return redirect()->route('dashboard');
    }
}

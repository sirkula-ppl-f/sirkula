<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class AuthenticateUser
{
    /**
     * Cari user dari kredensial login.
     *
     * Mengembalikan null bila email atau kata sandi salah, sehingga Fortify
     * menampilkan pesan generik auth.failed untuk kedua kasus.
     *
     * @throws ValidationException bila kredensial benar tetapi akun ditangguhkan
     */
    public function __invoke(Request $request): ?User
    {
        $email = Str::lower(trim((string) $request->input(Fortify::username())));

        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            return null;
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                Fortify::username() => __('auth.suspended'),
            ]);
        }

        return $user;
    }
}

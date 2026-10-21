<?php

namespace App\Actions\Fortify;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        // Email dibandingkan dalam huruf kecil agar "Budi@X.com" dan "budi@x.com" dianggap sama.
        $input['email'] = Str::lower(trim((string) ($input['email'] ?? '')));

        Validator::make($input, [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        // Hanya name, email, dan password yang diambil dari request.
        $user = new User([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        $user->forceFill([
            'role' => UserRole::Warga,
            'status' => UserStatus::Active,
        ])->save();

        return $user;
    }
}

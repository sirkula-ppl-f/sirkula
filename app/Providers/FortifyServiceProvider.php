<?php

namespace App\Providers;

use App\Actions\Fortify\AuthenticateUser;
use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use App\Http\Responses\PasswordResetLinkResponse;
use App\Http\Responses\RegisterResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);

        // Sukses dan gagal memakai respons yang sama agar tidak mengungkap email terdaftar.
        $this->app->singleton(SuccessfulPasswordResetLinkRequestResponse::class, PasswordResetLinkResponse::class);
        $this->app->singleton(FailedPasswordResetLinkRequestResponse::class, PasswordResetLinkResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::authenticateUsing(new AuthenticateUser);

        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));

        // Fitur konfirmasi kata sandi belum dipakai, tetapi route bawaan Fortify
        // (/user/confirm-password) tidak bisa dimatikan lewat features. Balas 404, bukan 500.
        Fortify::confirmPasswordView(fn () => abort(404));

        // Dipasang sebagai throttle:login di route POST /login, jadi semua percobaan (termasuk yang berhasil) ikut dihitung.
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)
                ->by($throttleKey)
                ->response(fn (Request $request, array $headers) => redirect()->route('login')
                    ->withInput($request->only(Fortify::username()))
                    ->withErrors([
                        Fortify::username() => __('auth.throttle', ['seconds' => $headers['Retry-After']]),
                    ]));
        });

        // Dipasang di POST /forgot-password dan POST /reset-password (lihat routes/web.php).
        // Nama route ikut di key, sehingga kedua endpoint dihitung terpisah.
        RateLimiter::for('password-reset', function (Request $request) {
            $route = $request->route()?->getName();
            $email = Str::lower((string) $request->input(Fortify::email()));

            $response = function (Request $request, array $headers) {
                // Permintaan tautan tetap mendapat respons generik yang sama persis.
                if ($request->routeIs('password.email')) {
                    return redirect()->route('password.request')->with('status', __('passwords.sent'));
                }

                return back()
                    ->withInput($request->only(Fortify::email()))
                    ->withErrors([
                        Fortify::email() => __('auth.throttle', ['seconds' => $headers['Retry-After']]),
                    ]);
            };

            return [
                // 5/menit per akun + IP.
                Limit::perMinute(5)
                    ->by(Str::transliterate($route.'|'.$email.'|'.$request->ip()))
                    ->response($response),
                // 20/menit per IP, membatasi satu IP yang mencoba banyak email.
                Limit::perMinute(20)
                    ->by($route.'|'.$request->ip())
                    ->response($response),
            ];
        });
    }
}

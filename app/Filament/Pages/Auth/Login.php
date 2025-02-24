<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Pipeline;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;


class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $response = parent::authenticate();
        } catch (ValidationException $e) {
            throw $e;
        }

        /** @var User $user */
        $user = Filament::auth()->user();

        if (strtolower($user->status) !== 'active') {
            Filament::auth()->logout();
            throw ValidationException::withMessages([
                'data.email' => 'Account Deactivated; Contact the administrator.',
            ]);
        }

        // Custom Logic 2: Update user's IP address
        Pipeline::send($user)
            ->through([
                function (User $user, Closure $next) {
                    $user->ip_address = null;
                    $user->save();
                    return $next($user);
                }
            ])
            ->then(fn (User $user) => $user->update(['ip_address' => request()->ip()]));

        $intendedUrl = Session::pull('url.intended');

        if ($intendedUrl) {
            return app(LoginResponse::class)->withCallback(function () use ($intendedUrl) {
                return redirect($intendedUrl);
            });
        } else {
            return $response;
        }
    }
}

<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\Http\Requests\LoginRequest;

class AuthenticateUser
{
    public function __invoke(LoginRequest $request): ?User
    {
        $credentials = $request->only('email', 'password');
        $user = User::where('email', $credentials['email'])->first();

        if (! $user) {
            return null;
        }

        $pepper = config('app.pepper');

        if ($user->salt) {
            $passwordWithSaltPepper = $credentials['password'].$user->salt.$pepper;

            if (! Hash::check($passwordWithSaltPepper, $user->password)) {
                return null;
            }
        } elseif (Hash::check($credentials['password'], $user->password)) {
            $salt = Str::random(32);
            $user->forceFill([
                'salt' => $salt,
                'password' => Hash::make($credentials['password'].$salt.$pepper),
            ])->save();
        } else {
            return null;
        }

        Auth::login($user);

        return $user;
    }
}

<?php

namespace App\Http\Controllers;

use App\Data\UserData;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected string $cookieName = 'auth_token';

    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->validated();

            if (!Auth::attempt($credentials)) {
                throw ValidationException::withMessages([
                    'username' => ['The provided credentials are incorrect.'],
                ]);
            }

            $user = Auth::user()->load('role', 'tenantBusiness');
            $token = $user->createToken('auth_token')->accessToken;

            $cookie = cookie(
                $this->cookieName,
                $token,
                60 * 24 * 7,   // 7 days, in minutes
                '/',           // path
                null,          // domain
                app()->environment('production'), // secure — HTTPS only in prod
                true,          // httpOnly
                false,         // raw
                'Strict'       // sameSite
            );

            return $this->success(
                UserData::from($user),
                'Login successful.'
            )->withCookie($cookie);
        } catch (ValidationException $e) {
            return $this->error($e);
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $token = $user->token();
            $token?->revoke();
        }

        $forgetCookie = Cookie::forget($this->cookieName);

        return $this->success(null, 'Logout successful.')
            ->withCookie($forgetCookie);
    }
}
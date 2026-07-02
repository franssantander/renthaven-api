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


    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Always return a generic success message regardless of whether the
        // email exists, to avoid leaking which emails are registered.
        return $this->success(
            null,
            'If an account with that email exists, a password reset link has been sent.'
        );
    }

    /**
     * Reset the password using the token from the emailed link.
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                // Revoke all existing Passport tokens on password change.
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $this->success(null, 'Your password has been reset successfully.');
    }

    /**
     * Resend the email verification link to the authenticated user.
     */
    public function sendVerificationEmail(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->success(null, 'Email already verified.');
        }

        $user->sendEmailVerificationNotification();

        return $this->success(null, 'Verification link sent.');
    }

    /**
     * Verify the user's email via the signed link.
     */
    public function verifyEmail(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return $this->error(null, 'Invalid verification link.', 403);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->success(null, 'Email already verified.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->success(null, 'Email verified successfully.');
    }
}
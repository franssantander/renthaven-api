<?php

namespace App\Http\Controllers;

use App\Data\UserData;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected string $cookieName = 'auth_token';

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();

        if (!$user->hasVerifiedEmail()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => ['Please verify your email address before logging in.'],
            ]);
        }

        $user->load('role', 'tenantBusiness');
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

    public function me(Request $request)
    {
        $user = $request->user()->load('role', 'tenantBusiness');
        $userData = UserData::from($user);
        $userData->permissions = $user->getPermissionMatrix();

        return $this->success($userData, 'User profile retrieved successfully.');
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $this->success(
            null,
            'A password reset link has been sent to your email address.'
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

        return redirect(config('app.frontend_url') . '/dashboard?verified=true');
    }
}
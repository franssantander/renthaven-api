<?php

namespace App\Http\Controllers;

use App\Data\UserData;
use App\Enum\AuditAction;
use App\Enum\AuditModule;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\MagicLinkRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyMagicLinkRequest;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use App\Services\AuditLog\AuditLogger;
use App\Services\Auth\MagicLinkService;
use App\Services\Auth\TokenService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger,
        protected MagicLinkService $magicLinkService,
        protected TokenService $tokenService,
    ) {}

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            $this->auditLogger->record(
                module: AuditModule::AUTH,
                action: AuditAction::LOGIN_FAILED,
                description: "Failed login attempt for username: {$credentials['username']}",
                context: ['username' => $credentials['username']],
            );
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();

        if (! $user->hasVerifiedEmail()) {
            Auth::logout();
            $this->auditLogger->record(
                module: AuditModule::AUTH,
                action: AuditAction::LOGIN_BLOCKED_UNVERIFIED,
                description: "Login blocked for {$user->email} — email not verified",
                auditable: $user,
            );
            throw ValidationException::withMessages([
                'email' => ['Please verify your email address before logging in.'],
            ]);
        }

        $user->load('role', 'tenantBusiness');

        $this->auditLogger->record(
            module: AuditModule::AUTH,
            action: AuditAction::LOGIN_SUCCESS,
            description: "{$user->email} logged in",
            auditable: $user,
        );

        [$accessCookie, $refreshCookie] = $this->tokenService->issue($user);

        return $this->success(
            UserData::from($user),
            'Login successful.'
        )->withCookie($accessCookie)->withCookie($refreshCookie);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $token = $user->token();

            if ($token) {
                $this->tokenService->revokeForAccessToken($token->id);
                $token->revoke();
            }
        }

        $this->auditLogger->record(
            module: AuditModule::AUTH,
            action: AuditAction::LOGIN_SUCCESS,
            description: "{$user->email} logout",
            auditable: $user,
        );

        [$forgetAccessCookie, $forgetRefreshCookie] = $this->tokenService->forgetCookies();

        return $this->success(null, 'Logout successful.')
            ->withCookie($forgetAccessCookie)
            ->withCookie($forgetRefreshCookie);
    }

    /**
     * Exchange a valid refresh token cookie for a new access/refresh token pair.
     */
    public function refresh(Request $request)
    {
        $plainRefreshToken = $request->cookie('refresh_token');

        if (! $plainRefreshToken) {
            return $this->error(null, 'Refresh token missing.', Response::HTTP_UNAUTHORIZED);
        }

        $result = $this->tokenService->rotate($plainRefreshToken);

        if (! $result) {
            $this->auditLogger->record(
                module: AuditModule::AUTH,
                action: AuditAction::TOKEN_REFRESH_FAILED,
                description: 'Refresh token rejected: invalid, expired, or already used',
            );

            return $this->error(null, 'Refresh token is invalid or has expired.', Response::HTTP_UNAUTHORIZED);
        }

        [$user, $accessCookie, $refreshCookie] = $result;

        $this->auditLogger->record(
            module: AuditModule::AUTH,
            action: AuditAction::TOKEN_REFRESHED,
            description: "{$user->email} refreshed their access token",
            auditable: $user,
        );

        return $this->success(null, 'Token refreshed successfully.')
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('role', 'tenantBusiness');
        $userData = UserData::from($user);
        $userData->permissions = $user->getPermissionMatrix();

        return $this->success($userData, 'User profile retrieved successfully.');
    }

    /**
     * Request a magic sign-in link for a renter account.
     */
    public function requestMagicLink(MagicLinkRequest $request)
    {
        $user = User::where('email', $request->validated('email'))
            ->whereHas('renterProfile')
            ->first();

        if ($user) {
            $token = $this->magicLinkService->issueFor($user);
            $user->notify(new MagicLinkNotification($token));

            $this->auditLogger->record(
                module: AuditModule::AUTH,
                action: AuditAction::MAGIC_LINK_REQUESTED,
                description: "Magic link requested for {$user->email}",
                auditable: $user,
            );
        }

        return $this->success(
            null,
            'If that email is associated with a renter account, a sign-in link has been sent.'
        );
    }

    /**
     * Consume a magic-link token and sign the renter in.
     */
    public function verifyMagicLink(VerifyMagicLinkRequest $request)
    {
        $user = $this->magicLinkService->consume($request->validated('token'));

        if (! $user) {
            return $this->error(null, 'This sign-in link is invalid or has expired.', 422);
        }

        $user->load('role', 'tenantBusiness');

        $this->auditLogger->record(
            module: AuditModule::AUTH,
            action: AuditAction::MAGIC_LINK_LOGIN_SUCCESS,
            description: "{$user->email} signed in via magic link",
            auditable: $user,
        );

        [$accessCookie, $refreshCookie] = $this->tokenService->issue($user);

        return $this->success(
            UserData::from($user),
            'Login successful.'
        )->withCookie($accessCookie)->withCookie($refreshCookie);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        Password::sendResetLink(
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

                // Revoke all existing Passport tokens and refresh tokens on password change.
                $user->tokens()->delete();
                $this->tokenService->revokeAllForUser($user);
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

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return $this->error(null, 'Invalid verification link.', 403);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->success(null, 'Email already verified.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect(config('app.frontend_url').'/dashboard?verified=true');
    }
}

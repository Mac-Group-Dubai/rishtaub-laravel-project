<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserQuota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // POST /api/v1/auth/register
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $requireVerification = (bool) config('rishtahub.require_email_verification');

        $user = DB::transaction(function () use ($data, $requireVerification) {
            $user = new User();
            $user->email = $data['email'];
            $user->password = $data['password'];
            $user->role = 'user'; // always a normal user, admins are made with the artisan command
            $user->email_verified_at = $requireVerification ? null : now();
            $user->save();

            // Same as the live handle_new_user trigger: one profile row per user
            $profile = new Profile();
            $profile->id = $user->id;
            $profile->email = $user->email;
            $profile->fill([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['country_code'] . $data['phone'], // live stores "+92" + number
                'gender' => $data['gender'],
                'dob' => $data['dob'],
                'onbehalf' => $data['onbehalf'] ?? 'self',
            ]);
            $profile->save();

            // Same as the live handle_new_profile_quota trigger
            UserQuota::create([
                'user_id' => $user->id,
                'quota_reset_at' => now()->addDays(30),
            ]);

            return $user;
        });

        // Live behaviour: no session until the emailed link is clicked
        if ($requireVerification) {
            $user->sendEmailVerificationNotification();

            return response()->json([
                'message' => 'We sent you a verification link.',
                'data' => ['email' => $user->email],
            ], 201);
        }

        return response()->json([
            'data' => [
                'token' => $user->createToken('api')->plainTextToken,
                'user' => new UserResource($user->load('profile')),
            ],
        ], 201);
    }

    // POST /api/v1/auth/login
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::where('email', strtolower(trim($credentials['email'])))->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Email or password is incorrect.',
                'code' => 'invalid_credentials',
            ], 401);
        }

        if (config('rishtahub.require_email_verification') && ! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please check your inbox and click the verification link before signing in.',
                'code' => 'email_not_verified',
            ], 403);
        }

        $user->load('profile');

        if ($user->role !== 'admin' && (! $user->profile || $user->profile->is_active === false)) {
            return response()->json([
                'message' => EnsureAccountActive::BLOCKED_MESSAGE,
                'code' => 'account_blocked',
            ], 403);
        }

        return response()->json([
            'data' => [
                'token' => $user->createToken('api')->plainTextToken,
                'user' => new UserResource($user),
            ],
        ]);
    }

    // POST /api/v1/auth/email/resend  (the live signup page has a "resend" button)
    public function resendVerification(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);

        $user = User::where('email', strtolower(trim($request->input('email'))))->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        // Same answer whether or not the email exists, so nobody can probe for accounts
        return response()->json(['message' => 'We sent you a verification link.']);
    }

    // GET /api/v1/auth/me
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('profile'));
    }

    // POST /api/v1/auth/logout
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }
}

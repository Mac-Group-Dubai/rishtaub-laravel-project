<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Same safety net as the live site: an already issued token must stop working
 * the moment the account is blocked (profiles.is_active = false) or the profile is gone.
 * Admins have no profile, so they are skipped.
 */
class EnsureAccountActive
{
    public const BLOCKED_MESSAGE = 'Your account has been blocked. Contact support if you think this is a mistake.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role !== 'admin') {
            $profile = $user->profile;

            if (! $profile || $profile->is_active === false) {
                $token = $user->currentAccessToken();
                if ($token && method_exists($token, 'delete')) {
                    $token->delete();
                }

                return response()->json([
                    'message' => self::BLOCKED_MESSAGE,
                    'code' => 'account_blocked',
                ], 403);
            }
        }

        return $next($request);
    }
}

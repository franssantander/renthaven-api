<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role->slug, $roles)) {
            return response()->json([
                'status' => 403,
                'message' => 'Access denied. Your role is not authorized to access this resource.',
                'data' => null,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

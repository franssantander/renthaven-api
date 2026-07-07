<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module, ...$actions): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (empty($actions)) {
            return response()->json(['message' => 'Forbidden: No actions specified for this endpoint.'], 403);
        }

        foreach ($actions as $action) {
            if ($user->hasPermission($module, $action)) {
                return $next($request);
            }
        }
        $formattedModule = ucwords(str_replace('_', ' ', $module));

        return response()->json([
            'status' => 403,
            'message' => "You do not have permission to perform this action on the '{$formattedModule}' module."
        ], 403);
    }
}
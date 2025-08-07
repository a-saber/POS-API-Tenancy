<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeaturePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $feature): Response
    {
        $user = $request->user();

        if (!$user || !$user->role || !$user->role->$feature) {
            return response()->json([
                'message' => 'Unauthorized: You do not have access to this feature: ' . $feature
            ], 403);
        }
        return $next($request);
    }
}

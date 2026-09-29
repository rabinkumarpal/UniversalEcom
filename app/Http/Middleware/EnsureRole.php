<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if ($user->hasRole('super-admin') || $user->hasRole('super_admin') || $user->hasRole('admin')) {
            return $next($request);
        }

        foreach ($roles as $role) {
            $normalizedRole = str_replace('_', '-', $role);
            $underscoredRole = str_replace('-', '_', $role);

            if ($user->hasRole($role) || $user->hasRole($normalizedRole) || $user->hasRole($underscoredRole)) {
                return $next($request);
            }
        }

        abort(403, 'Access denied. Insufficient role privileges.');
    }
}

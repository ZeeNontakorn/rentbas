<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffOrAdmin
{
    /**
     * Handle an incoming request.
     * Allows users with role 'admin' or 'staff' to pass through.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, ['admin', 'staff'], true)) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}

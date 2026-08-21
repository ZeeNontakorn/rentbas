<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermanentStaffOrAdmin
{
    /**
     * Handle an incoming request.
     * Allows users with role 'admin' or 'superadmin' to pass through.
     * Allows users with role 'staff' only if their membership_type is 'permanent'.
     * Excludes 'temporary' and 'intern' staff.
     */
    private const ADMIN_ROLES = ['admin', 'superadmin'];

    private const ALLOWED_STAFF_MEMBERSHIP_TYPES = ['permanent'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        $isAdmin = in_array($user->role, self::ADMIN_ROLES, true);

        $isEligibleStaff = $user->role === 'staff'
            && in_array($user->membership_type, self::ALLOWED_STAFF_MEMBERSHIP_TYPES, true);

        if (! $isAdmin && ! $isEligibleStaff) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}

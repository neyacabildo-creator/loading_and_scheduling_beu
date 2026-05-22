<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Path-based checks for auth pages (login, password reset).
 * Use these in global middleware — route names may not be resolved yet.
 */
class AuthPublicRoutes
{
    /** Paths that must stay reachable without an authenticated session. */
    private const PUBLIC_PATHS = [
        'login',
        'forgot-password',
        'reset-password',
        'csrf-refresh',
    ];

    public static function isPublicPath(Request $request): bool
    {
        $path = trim($request->path(), '/');

        if (in_array($path, self::PUBLIC_PATHS, true)) {
            return true;
        }

        if (str_starts_with($path, 'reset-password/')) {
            return true;
        }

        return $request->routeIs(
            'login',
            'password.request',
            'password.email',
            'password.reset',
            'password.store',
            'csrf.refresh'
        );
    }
}

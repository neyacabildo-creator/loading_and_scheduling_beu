<?php

namespace App\Http\Middleware;

use App\Support\AuthPublicRoutes;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block direct URL access to app pages without a valid login session.
 * Pasting a dashboard link into another browser has no session cookie → login page.
 */
class ProtectAuthenticatedRoutes
{
    /** URL path prefixes that require authentication. */
    private const PROTECTED_PREFIXES = [
        'admin',
        'teacher',
        'grade-school-admin',
        'grade-school-teacher',
        'shared-teacher',
        'principal',
        'dashboard',
        'api/admin',
        'api/teacher',
        'api/grade-school-admin',
        'api/grade-school-teacher',
        'api/shared-teacher',
        'api/stl',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }

        if (AuthPublicRoutes::isPublicPath($request)) {
            return $next($request);
        }

        if (! $this->pathRequiresAuth($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated. Please log in.'], 401);
        }

        return redirect()->guest(route('login'));
    }

    private function pathRequiresAuth(Request $request): bool
    {
        $path = trim($request->path(), '/');

        if ($path === '') {
            return false;
        }

        foreach (self::PROTECTED_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }
}

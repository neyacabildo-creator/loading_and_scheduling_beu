<?php

namespace App\Http\Middleware;

use App\Support\AuthPublicRoutes;
use App\Support\AuthSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default-deny: every URL except login/password reset requires a valid session
 * bound to this browser. Pasting a dashboard link into another browser has no
 * cookie → login page. Cached dashboard HTML must not be shown without auth.
 */
class ProtectAuthenticatedRoutes
{
    public function handle(Request $request, Closure $next): Response
    {
        if (AuthPublicRoutes::isPublicPath($request)) {
            return $next($request);
        }

        if (! $this->hasValidBrowserSession($request)) {
            if (Auth::check()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated. Please log in.'], 401);
            }

            return redirect()->guest(route('login'));
        }

        return $next($request);
    }

    private function hasValidBrowserSession(Request $request): bool
    {
        if (! Auth::check()) {
            return false;
        }

        if (! $request->hasSession() || ! $request->session()->isStarted()) {
            return false;
        }

        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return AuthSession::isActiveSession($user, $request->session()->getId());
    }
}

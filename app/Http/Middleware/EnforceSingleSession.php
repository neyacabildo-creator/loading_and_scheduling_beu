<?php

namespace App\Http\Middleware;

use App\Support\AuthPublicRoutes;
use App\Support\AuthSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSingleSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        if ($request->routeIs('logout', 'auth.heartbeat') || AuthPublicRoutes::isPublicPath($request)) {
            return $next($request);
        }

        $user = AuthSession::freshUser(Auth::user());
        $sessionId = $request->session()->getId();

        if (! AuthSession::isActiveSession($user, $sessionId)) {
            AuthSession::clearActiveSession($user);

            Auth::guard('web')->logout();
            $request->session()->forget('auth_tab_last_seen');
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'Your session is not valid on this browser. Please log in again.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 401);
            }

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        AuthSession::invalidateOtherSessions((int) $user->id, $sessionId);
        AuthSession::touchActiveSession($user);

        return $next($request);
    }
}

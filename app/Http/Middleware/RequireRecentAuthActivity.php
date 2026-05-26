<?php

namespace App\Http\Middleware;

use App\Support\AuthPublicRoutes;
use App\Support\AuthSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireRecentAuthActivity
{
    /**
     * Force a fresh login when the browser tab has been idle too long.
     *
     * The frontend keeps the session heartbeat alive while the tab is open.
     * If the tab is closed, the heartbeat stops and the next protected request
     * is redirected back to login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        if ($request->routeIs('auth.heartbeat', 'logout') || AuthPublicRoutes::isPublicPath($request)) {
            return $next($request);
        }

        $lastSeen = (int) $request->session()->get('auth_tab_last_seen', 0);

        // First protected request after login (or legacy session): start heartbeat tracking.
        if ($lastSeen === 0) {
            $request->session()->put('auth_tab_last_seen', now()->timestamp);

            return $next($request);
        }

        if ((now()->timestamp - $lastSeen) <= 1800) {
            return $next($request);
        }

        if ($user = Auth::user()) {
            AuthSession::releaseLoginLock($user);
            AuthSession::purgeAllSessionsForUser((int) $user->id);
        }

        Auth::guard('web')->logout();
        $request->session()->forget('auth_tab_last_seen');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Your session expired. Please log in again.'], 401);
        }

        return redirect()->route('login')->withErrors(['email' => 'Your session expired. Please log in again.']);
    }
}
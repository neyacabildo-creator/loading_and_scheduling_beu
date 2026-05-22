<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    /**
     * Reject mid-session if the account has been deactivated by an admin.
     *
     * Runs on every authenticated request so a deactivated user is
     * immediately locked out even if their session cookie is still valid.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Reload is_active fresh from the DB to prevent stale session data
            $user->refresh();

            if (! $user->is_active) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json(
                        ['message' => 'Your account has been deactivated. Please contact an administrator.'],
                        403
                    );
                }

                return redirect()->route('login')
                    ->withErrors(['email' => 'Your account has been deactivated. Please contact an administrator.']);
            }
        }

        return $next($request);
    }
}

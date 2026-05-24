<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginHistory;
use App\Support\AuthRedirectSupport;
use App\Support\AuthSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(AuthRedirectSupport::homeRouteName());
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        $sessionId = $request->session()->getId();

        AuthSession::assignActiveSession($user, $sessionId);
        AuthSession::invalidateOtherSessions((int) $user->id, $sessionId);
        AuthSession::rotateRememberToken($user);
        $request->session()->put('auth_tab_last_seen', now()->timestamp);

        LoginHistory::create([
            'user_id' => $user->id,
            'login_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        AuthRedirectSupport::repairKnownAdminAccounts($user);
        AuthRedirectSupport::normalizeTeacherSchoolLevel($user);
        AuthRedirectSupport::applyDepartmentSession($user);

        return redirect()->route(AuthRedirectSupport::homeRouteName($user));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $lastLogin = LoginHistory::where('user_id', Auth::id())
            ->whereNull('logout_at')
            ->latest()
            ->first();

        if ($lastLogin) {
            $lastLogin->update(['logout_at' => now()]);
        }

        if ($user = Auth::user()) {
            AuthSession::clearActiveSession($user);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Optional heartbeat endpoint (no-op; kept for layout scripts).
     */
    public function heartbeat(Request $request): Response
    {
        if (! Auth::check()) {
            return response()->noContent(401);
        }

        return response()->noContent();
    }

    /**
     * Handle logout on tab close.
     */
    public function logoutOnTabClose(Request $request): Response
    {
        $lastLogin = LoginHistory::where('user_id', Auth::id())
            ->whereNull('logout_at')
            ->latest()
            ->first();

        if ($lastLogin) {
            $lastLogin->update(['logout_at' => now()]);
        }

        if ($user = Auth::user()) {
            AuthSession::clearActiveSession($user);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}

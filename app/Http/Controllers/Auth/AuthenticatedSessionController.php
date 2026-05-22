<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginHistory;
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
            return redirect()->route('dashboard');
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

        LoginHistory::create([
            'user_id' => $user->id,
            'login_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            if (! $user->relationLoaded('role')) {
                $user->load('role');
            }

            if ($user->role && $user->role->name) {
                $roleName = $user->role->name;

                if (strpos($roleName, 'grade_school') !== false) {
                    session(['department' => 'grade_school']);
                } elseif (strpos($roleName, 'junior_high') !== false) {
                    session(['department' => 'junior_high']);
                }

                switch ($roleName) {
                    case 'principal':
                    case 'super_admin':
                        return redirect()->route('principal.dashboard');
                    case 'admin_grade_school':
                        return redirect()->route('grade-school-admin.dashboard');
                    case 'admin_junior_high':
                    case 'admin':
                        return redirect()->route('admin.dashboard');
                    case 'teacher_grade_school':
                        return redirect()->route('grade-school-teacher.dashboard');
                    case 'teacher_junior_high':
                    case 'teacher':
                        return redirect()->route('teacher.dashboard');
                    case 'shared_teacher':
                        return redirect()->route('shared-teacher.dashboard');
                }
            }
        } catch (\Exception $e) {
            // Fall through to generic dashboard
        }

        return redirect()->route('dashboard');
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

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();
        
        // Ensure role relationship is loaded
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }
        
        // Check if user has junior high admin role or principal role
        if ($user->role && $user->role->name) {
            if (in_array($user->role->name, ['admin_junior_high', 'admin', 'principal'])) {
                return $next($request);
            }
            // Grade school admin has their own separate dashboard
            if ($user->role->name === 'admin_grade_school') {
                return redirect()->route('grade-school-admin.dashboard');
            }
        }

        // Redirect non-admin users to their appropriate dashboard
        if ($user->role && $user->role->name && in_array($user->role->name, ['teacher', 'teacher_grade_school', 'teacher_junior_high'])) {
            return redirect('teacher/dashboard');
        }

        // No valid role match — return 403 to avoid redirect loop
        abort(403, 'Access denied.');
    }
}

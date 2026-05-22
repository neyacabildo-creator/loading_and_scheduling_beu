<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class IsTeacher
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

        // Check if user has junior high or generic teacher role
        $user = Auth::user();
        if ($user->role && in_array($user->role->name, ['teacher', 'teacher_junior_high'])) {
            return $next($request);
        }

        // Grade school teacher has their own separate dashboard
        if ($user->role && $user->role->name === 'teacher_grade_school') {
            return redirect()->route('grade-school-teacher.dashboard');
        }

        // Redirect admin users to their appropriate dashboard
        if ($user->role && $user->role->name) {
            if ($user->role->name === 'admin_grade_school') {
                return redirect()->route('grade-school-admin.dashboard');
            }
            if (in_array($user->role->name, ['admin_junior_high', 'admin'])) {
                return redirect()->route('admin.dashboard');
            }
        }

        return redirect('/');
    }
}

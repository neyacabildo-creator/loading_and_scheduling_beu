<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class IsGradeSchoolTeacher
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

        // Check if user is Grade School Teacher
        $user = Auth::user();
        if ($user->role && $user->role->name === 'teacher_grade_school' && $user->school_level === 'grade_school') {
            return $next($request);
        }

        // Redirect to appropriate dashboard based on role
        if ($user->role && $user->role->name === 'admin_grade_school') {
            return redirect()->route('grade-school-admin.dashboard');
        }

        if ($user->role && in_array($user->role->name, ['admin_junior_high', 'admin'])) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role && in_array($user->role->name, ['teacher_junior_high', 'teacher'])) {
            return redirect()->route('teacher.dashboard');
        }

        return redirect('/');
    }
}

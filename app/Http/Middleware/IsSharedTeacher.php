<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsSharedTeacher
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $role = Auth::user()->role?->name;

        if ($role === 'shared_teacher') {
            return $next($request);
        }

        // Redirect authenticated users to their own dashboard instead of 403
        return match ($role) {
            'admin_junior_high', 'admin' => redirect()->route('admin.dashboard'),
            'admin_grade_school'         => redirect()->route('grade-school-admin.dashboard'),
            'teacher', 'teacher_junior_high' => redirect()->route('teacher.dashboard'),
            'teacher_grade_school'       => redirect()->route('grade-school-teacher.dashboard'),
            'principal'                => redirect()->route('principal.dashboard'),
            default                      => redirect()->route('login'),
        };
    }
}

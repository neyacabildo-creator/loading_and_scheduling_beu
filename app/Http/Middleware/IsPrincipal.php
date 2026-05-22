<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class IsPrincipal
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();

        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        if ($user->role && $user->role->name === 'principal') {
            return $next($request);
        }

        // Redirect other authenticated users to their own dashboard
        $role = $user->role?->name;

        return match ($role) {
            'admin_grade_school'                => redirect()->route('grade-school-admin.dashboard'),
            'admin_junior_high', 'admin'        => redirect()->route('admin.dashboard'),
            'teacher_grade_school'              => redirect()->route('grade-school-teacher.dashboard'),
            'teacher_junior_high', 'teacher'    => redirect()->route('teacher.dashboard'),
            default                             => abort(403, 'Access denied.'),
        };
    }
}

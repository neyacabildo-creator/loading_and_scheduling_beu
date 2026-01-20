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

        // Check if user has admin role (admin_junior_high or admin_grade_school)
        $user = Auth::user();
        if ($user->role && strpos($user->role->name, 'admin') !== false) {
            return $next($request);
        }

        // Redirect non-admin users to their appropriate dashboard
        if ($user->role && $user->role->name === 'teacher') {
            return redirect('teacher/dashboard');
        }

        return redirect('/');
    }
}

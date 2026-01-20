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

        // Check if user has teacher role
        $user = Auth::user();
        if ($user->role && $user->role->name === 'teacher') {
            return $next($request);
        }

        // Redirect non-teacher users to their appropriate dashboard
        if ($user->role && (strpos($user->role->name, 'admin') !== false)) {
            return redirect('admin/dashboard');
        }

        return redirect('/');
    }
}

<?php

namespace App\Http\Middleware;

use App\Support\AuthRedirectSupport;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsGradeSchoolAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user()->loadMissing('role');
        $roleName = $user->role?->name;

        if (in_array($roleName, ['admin_grade_school', 'principal', 'super_admin'], true)) {
            return $next($request);
        }

        return AuthRedirectSupport::redirectAwayFromPortal($user, $request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Support\AuthRedirectSupport;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsTeacher
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        AuthRedirectSupport::normalizeTeacherSchoolLevel($user);

        if (AuthRedirectSupport::isJuniorHighTeacher($user)) {
            return $next($request);
        }

        return AuthRedirectSupport::redirectAwayFromPortal($user, $request);
    }
}

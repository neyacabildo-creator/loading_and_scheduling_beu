<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PreventAuthenticatedPageCache
{
    /**
     * Stop browsers from showing a cached dashboard when the session is invalid.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (Auth::check() || $this->isProtectedAppPath($request)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }

    private function isProtectedAppPath(Request $request): bool
    {
        $path = trim($request->path(), '/');

        foreach (['admin', 'teacher', 'grade-school-admin', 'grade-school-teacher', 'shared-teacher', 'principal', 'dashboard'] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }
}

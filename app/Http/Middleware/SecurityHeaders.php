<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Add security headers to every HTTP response.
     *
     * Covers: clickjacking (X-Frame-Options), MIME-sniffing (X-Content-Type-Options),
     * XSS filter (X-XSS-Protection), information leak (X-Powered-By removed),
     * referrer policy, CSP, and HSTS (only on HTTPS).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent the page from being embedded in a frame (clickjacking defence)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Stop browsers from MIME-sniffing the declared content-type
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Legacy XSS filter for older browsers
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Control how much referrer info is sent with navigations
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict browser feature APIs
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=()'
        );

        // Content-Security-Policy — tightened for a server-rendered Laravel app.
        // 'unsafe-inline' for styles/scripts is kept to support Blade + Vite during
        // development; tighten per-view with nonces when moving to production.
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
            "font-src 'self' https://fonts.gstatic.com data:; " .
            "img-src 'self' data: blob:; " .
            "connect-src 'self'; " .
            "frame-ancestors 'self'; " .
            "form-action 'self'; " .
            "base-uri 'self';"
        );

        // HSTS — only send over HTTPS; tell browsers to use HTTPS for 1 year
        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // Remove server-identifying headers to reduce fingerprinting
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}

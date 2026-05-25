<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Apply security headers to every response
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Rate limit web requests (configured in AppServiceProvider)
        $middleware->web(append: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':web',
        ]);

        // Reject deactivated accounts on every authenticated request
        $middleware->appendToGroup('web', \App\Http\Middleware\CheckUserActive::class);

        // One active session per account (must run before route auth gate)
        $middleware->appendToGroup('web', \App\Http\Middleware\EnforceSingleSession::class);

        // Default-deny: all pages except login/reset require this browser's session
        $middleware->appendToGroup('web', \App\Http\Middleware\ProtectAuthenticatedRoutes::class);

        // Idle tab timeout (~30 min without heartbeat) — force login again
        $middleware->appendToGroup('web', \App\Http\Middleware\RequireRecentAuthActivity::class);

        $middleware->alias([
            'recent.auth'      => \App\Http\Middleware\RequireRecentAuthActivity::class,
            'admin'            => \App\Http\Middleware\IsAdmin::class,
            'principal.admin'  => \App\Http\Middleware\IsPrincipal::class,
            'teacher'          => \App\Http\Middleware\IsTeacher::class,
            'grade.school.admin'   => \App\Http\Middleware\IsGradeSchoolAdmin::class,
            'grade.school.teacher' => \App\Http\Middleware\IsGradeSchoolTeacher::class,
            'shared.teacher'   => \App\Http\Middleware\IsSharedTeacher::class,
            'school.db'        => \App\Http\Middleware\SetSchoolDatabase::class,
            'active'           => \App\Http\Middleware\CheckUserActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // When CSRF/session expires, redirect to login instead of showing 419 page
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Session expired. Please refresh and try again.'], 419);
            }
            return redirect()->route('login')->withErrors(['email' => 'Your session expired. Please log in again.']);
        });
    })->create();

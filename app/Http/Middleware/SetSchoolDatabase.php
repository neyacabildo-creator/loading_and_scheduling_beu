<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetSchoolDatabase middleware
 *
 * Sets config('database.school_connection') before each request so that
 * operational models (Room, ClassSchedule, FacultyLoad, etc.) using the
 * UseSchoolConnection trait automatically query the correct database:
 *
 *   - admin/* routes (Junior High admin)  → mysql_jh (loading_scheduling_jh)
 *   - grade-school-admin/* routes          → mysql_gs (loading_scheduling_gs)
 *   - teacher/* routes (JH)                → mysql_jh (same admin DB as JH admin)
 *   - grade-school-teacher/* routes        → mysql_gs (same admin DB as GS admin)
 *
 * Teacher portal tables (subject_assignments, teacher_feedbacks, teacher_requests,
 * class_schedules, faculty_loads, etc.) all use this admin connection — not the
 * legacy mysql_*_teacher databases.
 *
 * Shared models (User, Role, LoginHistory) are unaffected because they
 * do not use the UseSchoolConnection trait and always use the default
 * connection (loading_scheduling).
 */
class SetSchoolDatabase
{
    public function handle(Request $request, Closure $next, string $schoolConnection): Response
    {
        // Set the school connection key so UseSchoolConnection trait picks it up
        config(['database.school_connection' => $schoolConnection]);

        return $next($request);
    }
}

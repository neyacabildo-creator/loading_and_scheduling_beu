<?php

namespace App\Http\Controllers;

use App\Support\ScheduleMonitoringSupport;
use App\Support\TeacherPresenceSupport;
use Illuminate\Support\Facades\DB;

class ScheduleMonitoringController extends Controller
{
    public function juniorHigh()
    {
        return $this->render(
            'mysql_jh',
            'junior_high',
            'junior-high-admin.monitoring-alerts',
            'layouts.admin',
            'admin.class-schedule',
            'admin.monitoring-alerts',
            'Monitoring & Alerts — Junior High'
        );
    }

    public function gradeSchool()
    {
        return $this->render(
            'mysql_gs',
            'grade_school',
            'grade-school-admin.monitoring-alerts',
            'layouts.grade-school-admin',
            'grade-school-admin.class-schedule',
            'grade-school-admin.monitoring-alerts',
            'Monitoring & Alerts — Grade School'
        );
    }

    private function render(
        string $connection,
        string $schoolLevel,
        string $view,
        string $layout,
        string $scheduleRoute,
        string $pageRoute,
        string $title
    ) {
        $monitoring = ScheduleMonitoringSupport::buildDashboard($connection, $schoolLevel);

        $sharedTeacherIds = DB::connection($connection)->table('shared_teachers')
            ->where('is_active', true)
            ->pluck('faculty_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $leaveBanner = TeacherPresenceSupport::collectActiveLeaveBannerData($connection, $sharedTeacherIds);

        return view($view, compact(
            'monitoring',
            'leaveBanner',
            'layout',
            'scheduleRoute',
            'pageRoute',
            'title',
            'schoolLevel'
        ));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacultyLoad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * LoadBalanceController
 *
 * Provides faculty load distribution data and smart rebalancing suggestions
 * to help admins identify overloaded/underutilized teachers and act quickly.
 *
 * Scoped to whatever DB connection the calling route's middleware has set
 * (mysql_jh for JH admin, mysql_gs for GS admin).
 */
class LoadBalanceController extends Controller
{
    // Thresholds (same as DSSEngine for consistency)
    private const OVERLOAD_HOURS   = 30;
    private const UNDERLOAD_HOURS  = 6;
    private const OVERLOAD_CLASSES = 5;

    public function indexJH(): \Illuminate\View\View
    {
        return view('junior-high-admin.load-balance');
    }

    public function indexGS(): \Illuminate\View\View
    {
        return view('grade-school-admin.load-balance');
    }

    /**
     * GET /api/admin/load-balance/data
     * GET /api/grade-school-admin/load-balance/data
     *
     * Returns all faculty loads grouped by teacher with status and suggestions.
     */
    public function data(Request $request): JsonResponse
    {
        $loads = FacultyLoad::where('status', '!=', 'inactive')->get();

        $userIds = $loads->pluck('faculty_id')->filter()->unique();
        $users   = $userIds->isNotEmpty()
            ? User::whereIn('id', $userIds)->get()->keyBy('id')
            : collect();

        // Group loads by faculty_id and build per-teacher summary
        $teachers = $loads->groupBy('faculty_id')->map(function ($teacherLoads, $facultyId) use ($users) {
            $user         = $users[$facultyId] ?? null;
            $totalHours   = (float) $teacherLoads->sum('load_hours');
            $totalClasses = (int)   $teacherLoads->sum('classes_assigned');

            $status = match (true) {
                $totalHours > self::OVERLOAD_HOURS || $totalClasses > self::OVERLOAD_CLASSES => 'overloaded',
                $totalHours < self::UNDERLOAD_HOURS                                          => 'underloaded',
                default                                                                       => 'balanced',
            };

            $displayName = $user
                ? (trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->name)
                : ($teacherLoads->first()->teacher_name ?? 'Unknown');

            return [
                'faculty_id'    => $facultyId,
                'name'          => $displayName,
                'total_hours'   => round($totalHours, 2),
                'total_classes' => $totalClasses,
                'status'        => $status,
                'excess_hours'  => $status === 'overloaded' ? round($totalHours - self::OVERLOAD_HOURS, 2) : 0,
                'spare_hours'   => $status === 'underloaded' ? round(self::OVERLOAD_HOURS - $totalHours, 2) : 0,
                'loads'         => $teacherLoads->map(fn($l) => [
                    'id'               => $l->id,
                    'subject'          => $l->subject,
                    'grade_level'      => $l->grade_level,
                    'department'       => $l->department,
                    'classes_assigned' => $l->classes_assigned,
                    'load_hours'       => (float) $l->load_hours,
                ])->values(),
            ];
        })->values()->sortByDesc('total_hours')->values();

        $overloaded  = $teachers->where('status', 'overloaded')->values();
        $underloaded = $teachers->where('status', 'underloaded')->values();
        $balanced    = $teachers->where('status', 'balanced')->values();

        $suggestions = $this->generateSuggestions($overloaded, $underloaded);

        return response()->json([
            'success' => true,
            'data'    => [
                'teachers'    => $teachers,
                'stats'       => [
                    'total'               => $teachers->count(),
                    'overloaded'          => $overloaded->count(),
                    'underloaded'         => $underloaded->count(),
                    'balanced'            => $balanced->count(),
                    'total_load_hours'    => round($loads->sum('load_hours'), 2),
                    'avg_load_hours'      => $teachers->count() > 0
                        ? round($teachers->avg('total_hours'), 2) : 0,
                    'max_hours'           => $teachers->max('total_hours') ?: 1,
                    'overload_threshold'  => self::OVERLOAD_HOURS,
                    'underload_threshold' => self::UNDERLOAD_HOURS,
                ],
                'suggestions' => $suggestions,
            ],
        ]);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function generateSuggestions($overloaded, $underloaded): array
    {
        $suggestions = [];

        foreach ($overloaded as $over) {
            $excessHours = $over['excess_hours'];
            // Sort movable loads by smallest first for minimal disruption
            $movable = collect($over['loads'])->sortBy('load_hours');

            $matched = false;
            foreach ($underloaded as $under) {
                $capacity = self::OVERLOAD_HOURS - $under['total_hours'];
                if ($capacity <= 0) continue;

                $toMove   = [];
                $moved    = 0.0;
                foreach ($movable as $load) {
                    if ($moved >= min($excessHours, $capacity)) break;
                    $toMove[] = $load;
                    $moved   += (float) $load['load_hours'];
                }

                if (!empty($toMove)) {
                    $suggestions[] = [
                        'type'            => 'rebalance',
                        'priority'        => 'high',
                        'from_teacher'    => $over['name'],
                        'from_faculty_id' => $over['faculty_id'],
                        'to_teacher'      => $under['name'],
                        'to_faculty_id'   => $under['faculty_id'],
                        'loads_to_move'   => array_values($toMove),
                        'hours_to_move'   => round($moved, 2),
                        'message'         => sprintf(
                            'Move ~%.1fh (%s) from %s (%.1fh total, overloaded) → %s (%.1fh total, has capacity).',
                            $moved,
                            collect($toMove)->pluck('subject')->filter()->implode(', ') ?: 'selected loads',
                            $over['name'],
                            $over['total_hours'],
                            $under['name'],
                            $under['total_hours']
                        ),
                    ];
                    $matched = true;
                    break; // one suggestion per overloaded teacher
                }
            }

            // No underloaded teachers to absorb — escalate
            if (!$matched) {
                $suggestions[] = [
                    'type'            => 'escalate',
                    'priority'        => 'medium',
                    'from_teacher'    => $over['name'],
                    'from_faculty_id' => $over['faculty_id'],
                    'message'         => sprintf(
                        '%s is overloaded by %.1fh but no available teacher has capacity. Review assignments or consider hiring.',
                        $over['name'],
                        $excessHours
                    ),
                ];
            }
        }

        // If all teachers are overloaded and none underloaded, suggest hiring
        if ($overloaded->count() > 0 && $underloaded->count() === 0) {
            $suggestions[] = [
                'type'     => 'hire',
                'priority' => 'medium',
                'message'  => sprintf(
                    '%d teacher(s) are overloaded with no underutilized teachers available. Consider hiring additional faculty to balance the load.',
                    $overloaded->count()
                ),
            ];
        }

        // Underloaded with no overloaded to feed from
        if ($underloaded->count() > 0 && $overloaded->count() === 0) {
            foreach ($underloaded as $under) {
                $suggestions[] = [
                    'type'            => 'assign',
                    'priority'        => 'low',
                    'to_teacher'      => $under['name'],
                    'to_faculty_id'   => $under['faculty_id'],
                    'message'         => sprintf(
                        '%s has only %.1fh assigned (%.1fh below minimum). Assign additional subjects or classes.',
                        $under['name'],
                        $under['total_hours'],
                        self::UNDERLOAD_HOURS - $under['total_hours']
                    ),
                ];
            }
        }

        return $suggestions;
    }
}

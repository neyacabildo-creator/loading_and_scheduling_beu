<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CombinedScheduleService
{
    /**
     * Approved active schedules from JH and GS databases for unified timetables.
     */
    public static function fetchApproved(): array
    {
        return array_merge(
            self::fetchFromConnection('mysql_jh', 'JH'),
            self::fetchFromConnection('mysql_gs', 'GS')
        );
    }

    private static function fetchFromConnection(string $connection, string $schoolLabel): array
    {
        try {
            $rows = DB::connection($connection)
                ->table('class_schedules')
                ->where('admin_approved', 1)
                ->whereNotIn('status', ['pending', 'rejected', 'deleted'])
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();

            if ($rows->isEmpty()) {
                return [];
            }

            $facultyIds  = $rows->pluck('faculty_id')->filter()->unique()->values();
            $roomIds     = $rows->pluck('room_id')->filter()->unique()->values();
            $approverIds = $rows->pluck('approved_by')->filter()->unique()->values();

            $users = $facultyIds->isNotEmpty()
                ? User::whereIn('id', $facultyIds->merge($approverIds)->unique())->get()->keyBy('id')
                : collect();

            $rooms = $roomIds->isNotEmpty()
                ? DB::connection($connection)->table('rooms')->whereIn('id', $roomIds)->get()->keyBy('id')
                : collect();

            return $rows->map(function ($row) use ($users, $rooms, $schoolLabel) {
                $faculty = $users->get($row->faculty_id);
                $room    = $rooms->get($row->room_id);

                return [
                    'id'             => $row->id,
                    'school'         => $schoolLabel,
                    'faculty_id'     => $row->faculty_id,
                    'subject'        => $row->subject,
                    'grade_level'    => $row->grade_level,
                    'section_name'   => $row->section_name,
                    'grade_section'  => trim(($row->grade_level ?? '') . ' ' . ($row->section_name ?? '')),
                    'room_id'        => $row->room_id,
                    'day_of_week'    => $row->day_of_week,
                    'schedule_date'  => $row->schedule_date,
                    'start_time'     => $row->start_time ? substr((string) $row->start_time, 0, 8) : null,
                    'end_time'       => $row->end_time ? substr((string) $row->end_time, 0, 8) : null,
                    'status'         => $row->status,
                    'admin_approved' => (bool) $row->admin_approved,
                    'faculty'        => $faculty ? [
                        'id'         => $faculty->id,
                        'name'       => trim(($faculty->first_name ?? '') . ' ' . ($faculty->last_name ?? '')) ?: $faculty->name,
                        'first_name' => $faculty->first_name,
                        'last_name'  => $faculty->last_name,
                    ] : null,
                    'room'           => $room ? [
                        'id'          => $room->id,
                        'room_number' => $room->room_number ?? null,
                        'building'    => $room->building ?? null,
                    ] : null,
                ];
            })->values()->all();
        } catch (\Throwable $e) {
            Log::warning("CombinedScheduleService [{$connection}]: " . $e->getMessage());

            return [];
        }
    }
}

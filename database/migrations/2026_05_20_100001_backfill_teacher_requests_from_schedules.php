<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill teacher_requests display columns from linked class_schedules (All Requests view).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            if (! Schema::connection($conn)->hasTable('teacher_requests')
                || ! Schema::connection($conn)->hasTable('class_schedules')) {
                continue;
            }

            $rows = DB::connection($conn)->table('teacher_requests')
                ->whereNotNull('schedule_id')
                ->get();

            foreach ($rows as $row) {
                $schedule = DB::connection($conn)->table('class_schedules')
                    ->where('id', (int) $row->schedule_id)
                    ->first();

                if (! $schedule) {
                    continue;
                }

                $updates = [];
                foreach (
                    [
                        'subject'              => 'subject',
                        'grade_level'          => 'grade_level',
                        'section_name'         => 'section_name',
                        'day_of_week'          => 'day_of_week',
                        'preferred_start_time' => 'start_time',
                        'preferred_end_time'   => 'end_time',
                    ] as $col => $scheduleCol
                ) {
                    $current = trim((string) ($row->{$col} ?? ''));
                    $fromSchedule = $schedule->{$scheduleCol} ?? null;
                    if ($current === '' && $fromSchedule !== null && trim((string) $fromSchedule) !== '') {
                        $value = (string) $fromSchedule;
                        if (in_array($col, ['preferred_start_time', 'preferred_end_time'], true)) {
                            $value = strlen($value) >= 5 ? substr($value, 0, 5) : $value;
                        }
                        $updates[$col] = $value;
                    }
                }

                if ($updates !== []) {
                    $updates['updated_at'] = now();
                    DB::connection($conn)->table('teacher_requests')->where('id', $row->id)->update($updates);
                }
            }
        }
    }

    public function down(): void
    {
        // display-only backfill
    }
};

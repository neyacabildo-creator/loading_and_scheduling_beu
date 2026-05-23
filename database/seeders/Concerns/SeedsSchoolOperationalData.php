<?php

namespace Database\Seeders\Concerns;

use App\Models\ClassSchedule;
use App\Models\FacultyLoad;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Collection;

trait SeedsSchoolOperationalData
{
    protected function withSchoolConnection(string $connection, callable $callback): void
    {
        $previous = config('database.school_connection');
        config(['database.school_connection' => $connection]);

        try {
            $callback();
        } finally {
            config(['database.school_connection' => $previous]);
        }
    }

    /** @param array<int, array<string, mixed>> $rooms */
    protected function seedRooms(array $rooms): void
    {
        foreach ($rooms as $room) {
            Room::updateOrCreate(
                ['room_number' => $room['room_number']],
                $room
            );
        }
    }

    /** @param Collection<int, User> $teachers */
    protected function seedFacultyLoads(Collection $teachers, string $notes): void
    {
        foreach ($teachers as $teacher) {
            FacultyLoad::updateOrCreate(
                ['faculty_id' => $teacher->id],
                [
                    'teacher_name'     => $teacher->name,
                    'classes_assigned' => random_int(3, 6),
                    'load_hours'       => (float) number_format(random_int(300, 600) / 100, 2, '.', ''),
                    'status'           => 'available',
                    'notes'            => $notes,
                ]
            );
        }
    }

    /**
     * @param Collection<int, User> $teachers
     * @param list<int> $roomIds
     * @param list<string> $subjects
     */
    protected function seedSampleSchedules(
        Collection $teachers,
        array $roomIds,
        array $subjects,
        string $gradePrefix,
        int $gradeMin,
        int $gradeMax,
        string $startTime,
        string $endTime,
    ): void {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        foreach ($teachers as $teacher) {
            for ($i = 0; $i < 3; $i++) {
                $gradeLevel = $gradePrefix.rand($gradeMin, $gradeMax);
                $sectionName = chr(65 + $i);
                $day = $days[array_rand($days)];

                ClassSchedule::firstOrCreate(
                    [
                        'faculty_id'   => $teacher->id,
                        'subject'      => $subjects[array_rand($subjects)],
                        'grade_level'  => $gradeLevel,
                        'section_name' => $sectionName,
                        'day_of_week'  => $day,
                        'start_time'   => $startTime,
                    ],
                    [
                        'room_id'        => $roomIds !== [] ? $roomIds[array_rand($roomIds)] : null,
                        'end_time'       => $endTime,
                        'status'         => 'pending',
                        'admin_approved' => false,
                    ]
                );
            }
        }
    }
}

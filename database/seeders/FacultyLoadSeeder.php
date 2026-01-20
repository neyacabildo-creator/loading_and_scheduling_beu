<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FacultyLoadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users (assuming they're created from UserFactory/RegisteredUserController)
        $users = DB::table('users')->limit(5)->get();

        $facultyData = [
            ['department' => 'High School', 'classes' => 5, 'load' => 6.5, 'status' => 'overloaded'],
            ['department' => 'Senior High', 'classes' => 4, 'load' => 4.2, 'status' => 'active'],
            ['department' => 'High School', 'classes' => 3, 'load' => 3.8, 'status' => 'active'],
            ['department' => 'College', 'classes' => 6, 'load' => 7.2, 'status' => 'overloaded'],
            ['department' => 'Senior High', 'classes' => 4, 'load' => 4.0, 'status' => 'active'],
        ];

        foreach ($users as $index => $user) {
            if (isset($facultyData[$index])) {
                DB::table('faculty_loads')->insert([
                    'faculty_id' => $user->id,
                    'department' => $facultyData[$index]['department'],
                    'classes_assigned' => $facultyData[$index]['classes'],
                    'load_hours' => $facultyData[$index]['load'],
                    'status' => $facultyData[$index]['status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Seed DSS Recommendations
        DB::table('dss_recommendations')->insert([
            [
                'type' => 'teacher_overload',
                'priority' => 'high',
                'issue' => 'Maria Santos has 6.5 hours/week load (exceeds 6.0 max)',
                'solution' => 'Reassign 1 class to another available teacher with lower load or hire additional faculty member.',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'class_balance',
                'priority' => 'medium',
                'issue' => 'Grade 10A has 35 students while 10B has 28 students',
                'solution' => 'Transfer 3-5 students from 10A to 10B to balance enrollment and improve teaching effectiveness.',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'room_utilization',
                'priority' => 'medium',
                'issue' => 'Lab Room 5 is only 40% utilized with 3 classes per day',
                'solution' => 'Consolidate lab classes or schedule more activities to increase room usage efficiency.',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'schedule_gap',
                'priority' => 'high',
                'issue' => '2-hour gap in Juan schedule on Tuesday afternoon',
                'solution' => 'Schedule additional class or professional development session during this gap to maximize productivity.',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'facility_assignment',
                'priority' => 'medium',
                'issue' => 'Science class assigned to regular classroom instead of lab',
                'solution' => 'Reassign to appropriate lab room with required equipment and facilities.',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'teacher_preference',
                'priority' => 'low',
                'issue' => 'Ana requested morning classes but assigned mostly afternoon slots',
                'solution' => 'Swap 2 afternoon classes with another teacher to accommodate preference without affecting schedule balance.',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Seed Rooms
        DB::table('rooms')->insert([
            ['room_number' => 'A101', 'building' => 'Main Building', 'capacity' => 35, 'has_laboratory' => false, 'has_projector' => true, 'has_ac' => true, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => 'A102', 'building' => 'Main Building', 'capacity' => 40, 'has_laboratory' => false, 'has_projector' => true, 'has_ac' => true, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => 'Lab101', 'building' => 'Science Building', 'capacity' => 25, 'has_laboratory' => true, 'has_projector' => true, 'has_ac' => true, 'status' => 'in-use', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => 'Lab102', 'building' => 'Science Building', 'capacity' => 25, 'has_laboratory' => true, 'has_projector' => true, 'has_ac' => true, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}

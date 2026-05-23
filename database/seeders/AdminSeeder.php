<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Room;
use App\Models\FacultyLoad;
use App\Models\ClassSchedule;
use App\Support\UserPassword;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create roles
        $principalRole = Role::firstOrCreate(
            ['name' => 'principal'],
            [
                'display_name' => 'Principal',
                'description'  => 'School Principal — full system control over both school levels.',
            ]
        );

        $adminGradeSchoolRole = Role::firstOrCreate(
            ['name' => 'admin_grade_school'],
            ['description' => 'Admin for Grade School']
        );

        $adminJuniorHighRole = Role::firstOrCreate(
            ['name' => 'admin_junior_high'],
            ['description' => 'Admin for Junior High School']
        );

        $teacherGradeSchoolRole = Role::firstOrCreate(
            ['name' => 'teacher_grade_school'],
            ['description' => 'Teacher for Grade School']
        );

        $teacherJuniorHighRole = Role::firstOrCreate(
            ['name' => 'teacher_junior_high'],
            ['description' => 'Teacher for Junior High School']
        );

        $teacherRole = Role::firstOrCreate(
            ['name' => 'teacher'],
            ['description' => 'Generic Teacher']
        );

        // ── Principal accounts (Principal & Secretary) ───────────────────────
        // Principals have full access to BOTH school levels and can handle
        // permission requests submitted by GS / JH admins.
        $principal = User::firstOrCreate(
            ['email' => 'principal@spup.edu.ph'],
            [
                'name'        => 'School Principal',
                'first_name'  => 'School',
                'last_name'   => 'Principal',
                'password'    => Hash::make('principal@spup2024'),
                'role_id'     => $principalRole->id,
                'position'    => 'Principal',
                'school_level'=> null, // principal spans both levels
                'is_active'   => true,
            ]
        );
        UserPassword::storeEncrypted($principal->id, 'principal@spup2024');

        $secretary = User::firstOrCreate(
            ['email' => 'secretary@spup.edu.ph'],
            [
                'name'        => 'School Secretary',
                'first_name'  => 'School',
                'last_name'   => 'Secretary',
                'password'    => Hash::make('secretary@spup2024'),
                'role_id'     => $principalRole->id,
                'position'    => 'Secretary',
                'school_level'=> null,
                'is_active'   => true,
            ]
        );
        UserPassword::storeEncrypted($secretary->id, 'secretary@spup2024');

        // Create Grade School Admin
        User::firstOrCreate(
            ['email' => 'admin.gradeschool@spup.edu.ph'],
            [
                'name' => 'Grade School Administrator',
                'first_name' => 'Grade School',
                'last_name' => 'Admin',
                'password' => Hash::make('admin123'),
                'role_id' => $adminGradeSchoolRole->id,
                'school_level' => 'grade_school',
                'is_active' => true,
            ]
        );

        // Create Junior High School Admin
        User::firstOrCreate(
            ['email' => 'admin.juniorhigh@spup.edu.ph'],
            [
                'name' => 'Junior High School Administrator',
                'first_name' => 'Junior High',
                'last_name' => 'Admin',
                'password' => Hash::make('admin123'),
                'role_id' => $adminJuniorHighRole->id,
                'school_level' => 'junior_high',
                'is_active' => true,
            ]
        );

        // Create Grade School Teachers
        $gradeSchoolTeachers = [
            ['name' => 'Maria Santos', 'first_name' => 'Maria', 'last_name' => 'Santos', 'email' => 'maria.santos@spup.edu.ph'],
            ['name' => 'John Reyes', 'first_name' => 'John', 'last_name' => 'Reyes', 'email' => 'john.reyes@spup.edu.ph'],
            ['name' => 'Anna Cruz', 'first_name' => 'Anna', 'last_name' => 'Cruz', 'email' => 'anna.cruz@spup.edu.ph'],
        ];

        foreach ($gradeSchoolTeachers as $teacher) {
            User::firstOrCreate(
                ['email' => $teacher['email']],
                [
                    'name' => $teacher['name'],
                    'first_name' => $teacher['first_name'],
                    'last_name' => $teacher['last_name'],
                    'password' => Hash::make('teacher123'),
                    'role_id' => $teacherGradeSchoolRole->id,
                    'position' => 'Teacher',
                    'school_level' => 'grade_school',
                    'is_active' => true,
                ]
            );
        }

        // Create Junior High School Teachers
        $juniorHighTeachers = [
            ['name' => 'Carlos Dela Cruz', 'first_name' => 'Carlos', 'last_name' => 'Dela Cruz', 'email' => 'carlos.delacruz@spup.edu.ph'],
            ['name' => 'Jennifer Lopez', 'first_name' => 'Jennifer', 'last_name' => 'Lopez', 'email' => 'jennifer.lopez@spup.edu.ph'],
            ['name' => 'Robert Santos Jr', 'first_name' => 'Robert', 'last_name' => 'Santos Jr', 'email' => 'robert.santos@spup.edu.ph'],
        ];

        foreach ($juniorHighTeachers as $teacher) {
            User::firstOrCreate(
                ['email' => $teacher['email']],
                [
                    'name' => $teacher['name'],
                    'first_name' => $teacher['first_name'],
                    'last_name' => $teacher['last_name'],
                    'password' => Hash::make('teacher123'),
                    'role_id' => $teacherJuniorHighRole->id,
                    'position' => 'Teacher',
                    'school_level' => 'junior_high',
                    'is_active' => true,
                ]
            );
        }

        // Shared teacher collections (main DB — users table only)
        $gradeSchoolTeacherUsers = User::where('role_id', $teacherGradeSchoolRole->id)->get();
        $juniorHighTeacherUsers  = User::where('role_id', $teacherJuniorHighRole->id)->get();
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        // ---------------------------------------------------------------
        // Grade School DB (mysql_gs) — rooms, faculty loads, schedules
        // These tables live in loading_scheduling_gs; no school_level col
        // ---------------------------------------------------------------
        config(['database.school_connection' => 'mysql_gs']);

        $gsRoomsData = [
            ['room_number' => 'GS-101', 'building' => 'Grade School - Science Wing',   'capacity' => 40, 'has_laboratory' => true,  'has_projector' => true, 'has_ac' => true, 'status' => 'available'],
            ['room_number' => 'GS-102', 'building' => 'Grade School - Science Wing',   'capacity' => 40, 'has_laboratory' => true,  'has_projector' => true, 'has_ac' => true, 'status' => 'available'],
            ['room_number' => 'GS-201', 'building' => 'Grade School - Academic Block', 'capacity' => 35, 'has_laboratory' => false, 'has_projector' => true, 'has_ac' => true, 'status' => 'available'],
            ['room_number' => 'GS-202', 'building' => 'Grade School - Academic Block', 'capacity' => 35, 'has_laboratory' => false, 'has_projector' => true, 'has_ac' => true, 'status' => 'available'],
        ];
        foreach ($gsRoomsData as $room) {
            Room::firstOrCreate(['room_number' => $room['room_number']], $room);
        }
        $gsRoomIds = Room::pluck('id')->toArray();

        foreach ($gradeSchoolTeacherUsers as $teacher) {
            FacultyLoad::firstOrCreate(
                ['faculty_id' => $teacher->id],
                [
                    'faculty_id'       => $teacher->id,
                    'teacher_name'     => $teacher->name,
                    'classes_assigned' => rand(3, 5),
                    'load_hours'       => number_format(rand(200, 500) / 100, 2),
                    'status'           => rand(0, 10) > 7 ? 'overloaded' : (rand(0, 10) > 5 ? 'part-time' : 'active'),
                    'notes'            => 'Grade School faculty load',
                ]
            );
        }

        $gsSubjects = ['Mathematics', 'English', 'Science', 'Social Studies', 'Filipino', 'Character Education'];
        foreach ($gradeSchoolTeacherUsers as $teacher) {
            for ($i = 0; $i < 3; $i++) {
                ClassSchedule::create([
                    'faculty_id'     => $teacher->id,
                    'subject'        => $gsSubjects[array_rand($gsSubjects)],
                    'grade_level'    => 'Grade '.rand(1, 6),
                    'section_name'   => chr(65 + $i),
                    'room_id'        => $gsRoomIds ? $gsRoomIds[array_rand($gsRoomIds)] : null,
                    'day_of_week'    => $daysOfWeek[array_rand($daysOfWeek)],
                    'start_time'     => '08:00',
                    'end_time'       => '09:00',
                    'status'         => 'pending',
                    'admin_approved' => false,
                ]);
            }
        }

        // ---------------------------------------------------------------
        // Junior High DB (mysql_jh) — rooms, faculty loads, schedules
        // ---------------------------------------------------------------
        config(['database.school_connection' => 'mysql_jh']);

        $jhsRoomsData = [
            ['room_number' => 'JHS-101', 'building' => 'Junior High - Science Lab',   'capacity' => 50, 'has_laboratory' => true,  'has_projector' => true, 'has_ac' => true, 'status' => 'available'],
            ['room_number' => 'JHS-102', 'building' => 'Junior High - Science Lab',   'capacity' => 50, 'has_laboratory' => true,  'has_projector' => true, 'has_ac' => true, 'status' => 'available'],
            ['room_number' => 'JHS-201', 'building' => 'Junior High - Main Building', 'capacity' => 45, 'has_laboratory' => false, 'has_projector' => true, 'has_ac' => true, 'status' => 'available'],
            ['room_number' => 'JHS-202', 'building' => 'Junior High - Main Building', 'capacity' => 45, 'has_laboratory' => false, 'has_projector' => true, 'has_ac' => true, 'status' => 'available'],
        ];
        foreach ($jhsRoomsData as $room) {
            Room::firstOrCreate(['room_number' => $room['room_number']], $room);
        }
        $jhsRoomIds = Room::pluck('id')->toArray();

        foreach ($juniorHighTeacherUsers as $teacher) {
            FacultyLoad::firstOrCreate(
                ['faculty_id' => $teacher->id],
                [
                    'faculty_id'       => $teacher->id,
                    'teacher_name'     => $teacher->name,
                    'classes_assigned' => rand(4, 6),
                    'load_hours'       => number_format(rand(300, 700) / 100, 2),
                    'status'           => rand(0, 10) > 7 ? 'overloaded' : (rand(0, 10) > 5 ? 'part-time' : 'active'),
                    'notes'            => 'Junior High School faculty load',
                ]
            );
        }

        $jhsSubjects = ['Algebra', 'English', 'Biology', 'History', 'Filipino', 'Physical Education', 'Computer Science'];
        foreach ($juniorHighTeacherUsers as $teacher) {
            for ($i = 0; $i < 3; $i++) {
                ClassSchedule::create([
                    'faculty_id'     => $teacher->id,
                    'subject'        => $jhsSubjects[array_rand($jhsSubjects)],
                    'grade_level'    => 'Grade '.rand(7, 10),
                    'section_name'   => chr(65 + $i),
                    'room_id'        => $jhsRoomIds ? $jhsRoomIds[array_rand($jhsRoomIds)] : null,
                    'day_of_week'    => $daysOfWeek[array_rand($daysOfWeek)],
                    'start_time'     => '09:30',
                    'end_time'       => '10:30',
                    'status'         => 'pending',
                    'admin_approved' => false,
                ]);
            }
        }

        // Reset to default connection
        config(['database.school_connection' => null]);

        $this->command->info('Admin seeder completed successfully!');
        $this->command->info('✓ Principal: principal@spup.edu.ph / principal@spup2024');
        $this->command->info('✓ Principal (Secretary): secretary@spup.edu.ph / secretary@spup2024');
        $this->command->info('✓ Grade School Admin: admin.gradeschool@spup.edu.ph / admin123');
        $this->command->info('✓ Junior High Admin: admin.juniorhigh@spup.edu.ph / admin123');
        $this->command->info('✓ Grade School Teachers: maria.santos@spup.edu.ph, john.reyes@spup.edu.ph, anna.cruz@spup.edu.ph (password: teacher123)');
        $this->command->info('✓ Junior High Teachers: carlos.delacruz@spup.edu.ph, jennifer.lopez@spup.edu.ph, robert.santos@spup.edu.ph (password: teacher123)');
    }
}

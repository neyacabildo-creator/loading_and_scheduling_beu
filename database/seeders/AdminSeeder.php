<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Support\UserPassword;
use Database\Seeders\Concerns\SeedsSchoolOperationalData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    use SeedsSchoolOperationalData;
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

        $gradeSchoolTeacherUsers = User::where('role_id', $teacherGradeSchoolRole->id)->get();
        $juniorHighTeacherUsers  = User::where('role_id', $teacherJuniorHighRole->id)->get();

        $this->withSchoolConnection('mysql_gs', function () use ($gradeSchoolTeacherUsers) {
            $this->seedRooms([
                ['room_number' => 'GS-101', 'building' => 'Grade School - Science Wing',   'capacity' => 40, 'has_laboratory' => true,  'has_projector' => true, 'has_ac' => true, 'status' => 'available'],
                ['room_number' => 'GS-102', 'building' => 'Grade School - Science Wing',   'capacity' => 40, 'has_laboratory' => true,  'has_projector' => true, 'has_ac' => true, 'status' => 'available'],
                ['room_number' => 'GS-201', 'building' => 'Grade School - Academic Block', 'capacity' => 35, 'has_laboratory' => false, 'has_projector' => true, 'has_ac' => true, 'status' => 'available'],
                ['room_number' => 'GS-202', 'building' => 'Grade School - Academic Block', 'capacity' => 35, 'has_laboratory' => false, 'has_projector' => true, 'has_ac' => true, 'status' => 'available'],
            ]);

            $this->seedFacultyLoads($gradeSchoolTeacherUsers, 'Grade School faculty load');

            $this->seedSampleSchedules(
                $gradeSchoolTeacherUsers,
                Room::pluck('id')->all(),
                ['Mathematics', 'English', 'Science', 'Social Studies', 'Filipino', 'Character Education'],
                'Grade ',
                1,
                6,
                '08:00:00',
                '09:00:00',
            );
        });

        $this->withSchoolConnection('mysql_jh', function () use ($juniorHighTeacherUsers) {
            $this->seedRooms([
                ['room_number' => 'JHS-101', 'building' => 'Junior High - Science Lab',   'capacity' => 50, 'has_laboratory' => true,  'has_projector' => true, 'has_ac' => true, 'status' => 'available'],
                ['room_number' => 'JHS-102', 'building' => 'Junior High - Science Lab',   'capacity' => 50, 'has_laboratory' => true,  'has_projector' => true, 'has_ac' => true, 'status' => 'available'],
                ['room_number' => 'JHS-201', 'building' => 'Junior High - Main Building', 'capacity' => 45, 'has_laboratory' => false, 'has_projector' => true, 'has_ac' => true, 'status' => 'available'],
                ['room_number' => 'JHS-202', 'building' => 'Junior High - Main Building', 'capacity' => 45, 'has_laboratory' => false, 'has_projector' => true, 'has_ac' => true, 'status' => 'available'],
            ]);

            $this->seedFacultyLoads($juniorHighTeacherUsers, 'Junior High School faculty load');

            $this->seedSampleSchedules(
                $juniorHighTeacherUsers,
                Room::pluck('id')->all(),
                ['Algebra', 'English', 'Biology', 'History', 'Filipino', 'Physical Education', 'Computer Science'],
                'Grade ',
                7,
                10,
                '09:30:00',
                '10:30:00',
            );
        });

        $this->command->info('Admin seeder completed successfully!');
        $this->command->info('✓ Principal: principal@spup.edu.ph / principal@spup2024');
        $this->command->info('✓ Principal (Secretary): secretary@spup.edu.ph / secretary@spup2024');
        $this->command->info('✓ Grade School Admin: admin.gradeschool@spup.edu.ph / admin123');
        $this->command->info('✓ Junior High Admin: admin.juniorhigh@spup.edu.ph / admin123');
        $this->command->info('✓ Grade School Teachers: maria.santos@spup.edu.ph, john.reyes@spup.edu.ph, anna.cruz@spup.edu.ph (password: teacher123)');
        $this->command->info('✓ Junior High Teachers: carlos.delacruz@spup.edu.ph, jennifer.lopez@spup.edu.ph, robert.santos@spup.edu.ph (password: teacher123)');
    }
}

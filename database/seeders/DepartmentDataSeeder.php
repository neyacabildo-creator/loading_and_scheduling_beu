<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\Concerns\SeedsSchoolOperationalData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DepartmentDataSeeder extends Seeder
{
    use SeedsSchoolOperationalData;
    /**
     * Fix existing users and ensure proper department accounts exist.
     * - Fixes wrong school_level values
     * - Upgrades generic 'teacher' role to department-specific roles
     * - Creates missing admin/teacher accounts per department
     */
    public function run(): void
    {
        // ----------------------------------------------------------------
        // 1. Ensure all 4 roles exist
        // ----------------------------------------------------------------
        $gsAdminRole  = Role::firstOrCreate(['name' => 'admin_grade_school'],  ['display_name' => 'Admin - Grade School',       'description' => 'Administrator for grade school section']);
        $jhAdminRole  = Role::firstOrCreate(['name' => 'admin_junior_high'],   ['display_name' => 'Admin - Junior High School',  'description' => 'Administrator for junior high school section']);
        $gsTeachRole  = Role::firstOrCreate(['name' => 'teacher_grade_school'],['display_name' => 'Teacher - Grade School',      'description' => 'Teacher for grade school section']);
        $jhTeachRole  = Role::firstOrCreate(['name' => 'teacher_junior_high'], ['display_name' => 'Teacher - Junior High School','description' => 'Teacher for junior high school section']);

        // ----------------------------------------------------------------
        // 2. Fix wrong school_level values ('High School' → correct enum)
        // ----------------------------------------------------------------
        DB::table('users')
            ->where('school_level', 'High School')
            ->whereIn('email', [
                'maria.santos@spup.edu.ph',
                'john.reyes@spup.edu.ph',
                'anna.cruz@spup.edu.ph',
            ])
            ->update(['school_level' => 'grade_school', 'role_id' => $gsTeachRole->id]);

        DB::table('users')
            ->where('school_level', 'High School')
            ->whereIn('email', [
                'carlos.delacruz@spup.edu.ph',
                'jennifer.lopez@spup.edu.ph',
            ])
            ->update(['school_level' => 'junior_high', 'role_id' => $jhTeachRole->id]);

        // Fix any remaining 'High School' entries: default to junior_high
        DB::table('users')
            ->where('school_level', 'High School')
            ->update(['school_level' => 'junior_high']);

        // Fix users with 'teacher' role that have school_level set
        $genericTeacherRole = Role::where('name', 'teacher')->first();
        if ($genericTeacherRole) {
            DB::table('users')
                ->where('role_id', $genericTeacherRole->id)
                ->where('school_level', 'grade_school')
                ->update(['role_id' => $gsTeachRole->id]);

            DB::table('users')
                ->where('role_id', $genericTeacherRole->id)
                ->where('school_level', 'junior_high')
                ->update(['role_id' => $jhTeachRole->id]);
        }

        // Fix admin accounts with null school_level
        DB::table('users')
            ->where('role_id', $gsAdminRole->id)
            ->whereNull('school_level')
            ->update(['school_level' => 'grade_school']);

        DB::table('users')
            ->where('role_id', $jhAdminRole->id)
            ->whereNull('school_level')
            ->update(['school_level' => 'junior_high']);

        // ----------------------------------------------------------------
        // 3. Create canonical admin accounts (one per department)
        // ----------------------------------------------------------------
        User::updateOrCreate(
            ['email' => 'admin.gradeschool@spup.edu.ph'],
            [
                'name'         => 'Grade School Administrator',
                'first_name'   => 'Grade School',
                'last_name'    => 'Admin',
                'password'     => Hash::make('admin123'),
                'role_id'      => $gsAdminRole->id,
                'school_level' => 'grade_school',
                'is_active'    => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin.juniorhigh@spup.edu.ph'],
            [
                'name'         => 'Junior High School Administrator',
                'first_name'   => 'Junior High',
                'last_name'    => 'Admin',
                'password'     => Hash::make('admin123'),
                'role_id'      => $jhAdminRole->id,
                'school_level' => 'junior_high',
                'is_active'    => true,
            ]
        );

        // ----------------------------------------------------------------
        // 4. Ensure Grade School teachers exist
        // ----------------------------------------------------------------
        $gsTeachers = [
            ['email' => 'maria.santos@spup.edu.ph',   'name' => 'Maria Santos',   'first_name' => 'Maria',   'last_name' => 'Santos'],
            ['email' => 'john.reyes@spup.edu.ph',     'name' => 'John Reyes',     'first_name' => 'John',    'last_name' => 'Reyes'],
            ['email' => 'anna.cruz@spup.edu.ph',      'name' => 'Anna Cruz',      'first_name' => 'Anna',    'last_name' => 'Cruz'],
            ['email' => 'lisa.garcia@spup.edu.ph',    'name' => 'Lisa Garcia',    'first_name' => 'Lisa',    'last_name' => 'Garcia'],
            ['email' => 'mark.rivera@spup.edu.ph',    'name' => 'Mark Rivera',    'first_name' => 'Mark',    'last_name' => 'Rivera'],
        ];

        foreach ($gsTeachers as $t) {
            User::updateOrCreate(
                ['email' => $t['email']],
                [
                    'name'         => $t['name'],
                    'first_name'   => $t['first_name'],
                    'last_name'    => $t['last_name'],
                    'password'     => Hash::make('teacher123'),
                    'role_id'      => $gsTeachRole->id,
                    'position'     => 'Teacher',
                    'school_level' => 'grade_school',
                    'is_active'    => true,
                ]
            );
        }

        // ----------------------------------------------------------------
        // 5. Ensure Junior High School teachers exist
        // ----------------------------------------------------------------
        $jhTeachers = [
            ['email' => 'carlos.delacruz@spup.edu.ph',  'name' => 'Carlos Dela Cruz',  'first_name' => 'Carlos',   'last_name' => 'Dela Cruz'],
            ['email' => 'jennifer.lopez@spup.edu.ph',   'name' => 'Jennifer Lopez',    'first_name' => 'Jennifer', 'last_name' => 'Lopez'],
            ['email' => 'robert.santos@spup.edu.ph',    'name' => 'Robert Santos Jr',  'first_name' => 'Robert',   'last_name' => 'Santos Jr'],
            ['email' => 'grace.tan@spup.edu.ph',        'name' => 'Grace Tan',         'first_name' => 'Grace',    'last_name' => 'Tan'],
            ['email' => 'michael.aquino@spup.edu.ph',   'name' => 'Michael Aquino',    'first_name' => 'Michael',  'last_name' => 'Aquino'],
        ];

        foreach ($jhTeachers as $t) {
            User::updateOrCreate(
                ['email' => $t['email']],
                [
                    'name'         => $t['name'],
                    'first_name'   => $t['first_name'],
                    'last_name'    => $t['last_name'],
                    'password'     => Hash::make('teacher123'),
                    'role_id'      => $jhTeachRole->id,
                    'position'     => 'Teacher',
                    'school_level' => 'junior_high',
                    'is_active'    => true,
                ]
            );
        }

        // ----------------------------------------------------------------
        // 6. Ensure rooms exist in each school database
        // ----------------------------------------------------------------
        $gradeSchoolRooms = [
            ['room_number' => 'GS-101', 'building' => 'Grade School - Science Wing',   'capacity' => 40, 'has_laboratory' => true,  'has_projector' => true,  'has_ac' => true,  'status' => 'available'],
            ['room_number' => 'GS-102', 'building' => 'Grade School - Science Wing',   'capacity' => 40, 'has_laboratory' => true,  'has_projector' => true,  'has_ac' => true,  'status' => 'available'],
            ['room_number' => 'GS-201', 'building' => 'Grade School - Academic Block', 'capacity' => 35, 'has_laboratory' => false, 'has_projector' => true,  'has_ac' => true,  'status' => 'available'],
            ['room_number' => 'GS-202', 'building' => 'Grade School - Academic Block', 'capacity' => 35, 'has_laboratory' => false, 'has_projector' => true,  'has_ac' => true,  'status' => 'available'],
            ['room_number' => 'GS-301', 'building' => 'Grade School - Arts Room',      'capacity' => 30, 'has_laboratory' => false, 'has_projector' => false, 'has_ac' => false, 'status' => 'available'],
        ];

        $juniorHighRooms = [
            ['room_number' => 'JHS-101', 'building' => 'Junior High - Science Lab',    'capacity' => 50, 'has_laboratory' => true,  'has_projector' => true,  'has_ac' => true,  'status' => 'available'],
            ['room_number' => 'JHS-102', 'building' => 'Junior High - Science Lab',    'capacity' => 50, 'has_laboratory' => true,  'has_projector' => true,  'has_ac' => true,  'status' => 'available'],
            ['room_number' => 'JHS-201', 'building' => 'Junior High - Main Building',  'capacity' => 45, 'has_laboratory' => false, 'has_projector' => true,  'has_ac' => true,  'status' => 'available'],
            ['room_number' => 'JHS-202', 'building' => 'Junior High - Main Building',  'capacity' => 45, 'has_laboratory' => false, 'has_projector' => true,  'has_ac' => true,  'status' => 'available'],
            ['room_number' => 'JHS-301', 'building' => 'Junior High - Computer Lab',   'capacity' => 40, 'has_laboratory' => true,  'has_projector' => true,  'has_ac' => true,  'status' => 'available'],
        ];

        $gsTeacherUsers = User::where('role_id', $gsTeachRole->id)->get();
        $jhTeacherUsers = User::where('role_id', $jhTeachRole->id)->get();

        $this->withSchoolConnection('mysql_gs', function () use ($gradeSchoolRooms, $gsTeacherUsers) {
            $this->seedRooms($gradeSchoolRooms);
            $this->seedFacultyLoads($gsTeacherUsers, 'Grade School faculty load');
        });

        $this->withSchoolConnection('mysql_jh', function () use ($juniorHighRooms, $jhTeacherUsers) {
            $this->seedRooms($juniorHighRooms);
            $this->seedFacultyLoads($jhTeacherUsers, 'Junior High School faculty load');
        });

        $this->command->info('');
        $this->command->info('=== Department Data Seeded Successfully ===');
        $this->command->info('');
        $this->command->info('GRADE SCHOOL');
        $this->command->info('  Admin  : admin.gradeschool@spup.edu.ph   / admin123');
        $this->command->info('  Teachers: ' . User::where('role_id', $gsTeachRole->id)->count() . ' accounts (password: teacher123)');
        $this->command->info('');
        $this->command->info('JUNIOR HIGH SCHOOL');
        $this->command->info('  Admin  : admin.juniorhigh@spup.edu.ph    / admin123');
        $this->command->info('  Teachers: ' . User::where('role_id', $jhTeachRole->id)->count() . ' accounts (password: teacher123)');
        $this->command->info('');
    }
}

<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'principal',
                'display_name' => 'Principal',
                'description' => 'School Principal — full system control over both school levels.',
            ],
            [
                'name' => 'admin_junior_high',
                'display_name' => 'Admin - Junior High School',
                'description' => 'Administrator for junior high school section',
            ],
            [
                'name' => 'admin_grade_school',
                'display_name' => 'Admin - Grade School',
                'description' => 'Administrator for grade school section',
            ],
            [
                'name' => 'teacher_grade_school',
                'display_name' => 'Teacher - Grade School',
                'description' => 'Teacher for grade school section',
            ],
            [
                'name' => 'teacher_junior_high',
                'display_name' => 'Teacher - Junior High School',
                'description' => 'Teacher for junior high school section',
            ],
            [
                'name' => 'teacher',
                'display_name' => 'Teacher (Generic)',
                'description' => 'Generic teacher role (deprecated - use specific school roles)',
            ],
            [
                'name' => 'admin',
                'display_name' => 'Admin (Generic)',
                'description' => 'School admin — requires school level (Grade School or Junior High)',
            ],
            [
                'name' => 'shared_teacher',
                'display_name' => 'Shared Teacher',
                'description' => 'Teacher assigned across Grade School and Junior High',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}

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
                'name' => 'admin_junior_high',
                'display_name' => 'Admin - Junior High',
                'description' => 'Administrator for junior high school',
            ],
            [
                'name' => 'admin_grade_school',
                'display_name' => 'Admin - Grade School',
                'description' => 'Administrator for grade school',
            ],
            [
                'name' => 'teacher',
                'display_name' => 'Teacher',
                'description' => 'Teacher role',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}

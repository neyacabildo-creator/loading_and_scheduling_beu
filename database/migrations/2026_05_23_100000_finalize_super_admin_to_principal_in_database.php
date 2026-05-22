<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures all database records use "principal" instead of legacy "super_admin".
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->migrateRolesAndUsers();
        $this->migratePrincipalAllUsersSnapshot();
        $this->migrateScheduleApprovalColumns();
    }

    public function down(): void
    {
        // One-way data normalization.
    }

    private function migrateRolesAndUsers(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $principalRole = DB::table('roles')->where('name', 'principal')->first();
        $legacyRole = DB::table('roles')->where('name', 'super_admin')->first();

        if (! $principalRole) {
            if ($legacyRole) {
                DB::table('roles')->where('id', $legacyRole->id)->update([
                    'name'         => 'principal',
                    'display_name' => 'Principal',
                    'description'  => 'School Principal — full system control over both school levels.',
                    'updated_at'   => now(),
                ]);
                $principalRole = DB::table('roles')->where('name', 'principal')->first();
            } else {
                DB::table('roles')->insert([
                    'name'         => 'principal',
                    'display_name' => 'Principal',
                    'description'  => 'School Principal — full system control over both school levels.',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
                $principalRole = DB::table('roles')->where('name', 'principal')->first();
            }
        } else {
            DB::table('roles')->where('id', $principalRole->id)->update([
                'display_name' => 'Principal',
                'description'  => 'School Principal — full system control over both school levels.',
                'updated_at'   => now(),
            ]);
        }

        if ($legacyRole && $principalRole && (int) $legacyRole->id !== (int) $principalRole->id) {
            if (Schema::hasTable('users')) {
                DB::table('users')
                    ->where('role_id', $legacyRole->id)
                    ->update(['role_id' => $principalRole->id]);
            }
            DB::table('roles')->where('id', $legacyRole->id)->delete();
        }
    }

    private function migratePrincipalAllUsersSnapshot(): void
    {
        if (! Schema::connection('mysql_principal')->hasTable('all_users')) {
            return;
        }

        DB::connection('mysql_principal')->table('all_users')
            ->whereIn('role', ['super_admin', 'Super Administrator', 'Super Admin', 'super admin'])
            ->update(['role' => 'principal', 'updated_at' => now()]);

        // Re-sync from main users table so role labels stay accurate
        if (Schema::hasTable('users') && Schema::hasTable('roles')) {
            $users = DB::table('users')
                ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                ->select('users.id', 'users.name', 'users.email', 'users.school_level', 'users.is_active', 'roles.name as role_name')
                ->get();

            foreach ($users as $user) {
                $roleName = $user->role_name === 'super_admin' ? 'principal' : ($user->role_name ?? 'user');
                DB::connection('mysql_principal')->table('all_users')->updateOrInsert(
                    ['id' => $user->id],
                    [
                        'name'         => $user->name,
                        'email'        => $user->email,
                        'role'         => $roleName,
                        'school_level' => $user->school_level ?? 'system',
                        'is_active'    => (int) $user->is_active,
                        'synced_at'    => now(),
                        'updated_at'   => now(),
                    ]
                );
            }
        }
    }

    private function migrateScheduleApprovalColumns(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            if (! Schema::connection($conn)->hasTable('class_schedules')) {
                continue;
            }

            $schema = Schema::connection($conn);

            if ($schema->hasColumn('class_schedules', 'super_admin_approved')
                && ! $schema->hasColumn('class_schedules', 'principal_approved')) {
                DB::connection($conn)->statement(
                    'ALTER TABLE class_schedules CHANGE super_admin_approved principal_approved TINYINT(1) NOT NULL DEFAULT 0'
                );
            }

            if ($schema->hasColumn('class_schedules', 'super_admin_approved_at')
                && ! $schema->hasColumn('class_schedules', 'principal_approved_at')) {
                DB::connection($conn)->statement(
                    'ALTER TABLE class_schedules CHANGE super_admin_approved_at principal_approved_at TIMESTAMP NULL DEFAULT NULL'
                );
            }

            if ($schema->hasColumn('class_schedules', 'super_admin_approved_by')
                && ! $schema->hasColumn('class_schedules', 'principal_approved_by')) {
                DB::connection($conn)->statement(
                    'ALTER TABLE class_schedules CHANGE super_admin_approved_by principal_approved_by BIGINT UNSIGNED NULL DEFAULT NULL'
                );
            }
        }
    }
};

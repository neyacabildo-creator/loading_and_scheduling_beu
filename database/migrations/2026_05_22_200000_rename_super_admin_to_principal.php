<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles')) {
            DB::table('roles')
                ->where('name', 'super_admin')
                ->update([
                    'name'         => 'principal',
                    'display_name' => 'Principal',
                    'description'  => 'School Principal — full system control over both school levels.',
                ]);
        }

        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            if (! Schema::connection($conn)->hasTable('class_schedules')) {
                continue;
            }

            $this->renameApprovalColumn($conn, 'super_admin_approved', 'principal_approved', 'TINYINT(1) NOT NULL DEFAULT 0');
            $this->renameApprovalColumn($conn, 'super_admin_approved_at', 'principal_approved_at', 'TIMESTAMP NULL DEFAULT NULL');
            $this->renameApprovalColumn($conn, 'super_admin_approved_by', 'principal_approved_by', 'BIGINT UNSIGNED NULL DEFAULT NULL');
        }
    }

    private function renameApprovalColumn(string $conn, string $from, string $to, string $definition): void
    {
        $schema = Schema::connection($conn);

        if (! $schema->hasColumn('class_schedules', $from)) {
            return;
        }

        if ($schema->hasColumn('class_schedules', $to)) {
            DB::connection($conn)->statement(
                "UPDATE class_schedules SET {$to} = COALESCE({$to}, {$from}) WHERE {$from} IS NOT NULL"
            );
            $schema->table('class_schedules', function ($table) use ($from) {
                $table->dropColumn($from);
            });

            return;
        }

        DB::connection($conn)->statement(
            "ALTER TABLE class_schedules CHANGE {$from} {$to} {$definition}"
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('roles')) {
            DB::table('roles')
                ->where('name', 'principal')
                ->update([
                    'name'         => 'super_admin',
                    'display_name' => 'Super Administrator',
                ]);
        }

        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            if (! Schema::connection($conn)->hasTable('class_schedules')) {
                continue;
            }

            $this->renameApprovalColumn($conn, 'principal_approved', 'super_admin_approved', 'TINYINT(1) NOT NULL DEFAULT 0');
            $this->renameApprovalColumn($conn, 'principal_approved_at', 'super_admin_approved_at', 'TIMESTAMP NULL DEFAULT NULL');
            $this->renameApprovalColumn($conn, 'principal_approved_by', 'super_admin_approved_by', 'BIGINT UNSIGNED NULL DEFAULT NULL');
        }
    }
};

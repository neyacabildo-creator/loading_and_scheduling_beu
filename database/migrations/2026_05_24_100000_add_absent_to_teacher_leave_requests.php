<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $connections = ['mysql_jh', 'mysql_gs'];

    public function up(): void
    {
        foreach ($this->connections as $conn) {
            if (! Schema::connection($conn)->hasTable('teacher_leave_requests')) {
                continue;
            }

            DB::connection($conn)->statement("
                ALTER TABLE teacher_leave_requests
                MODIFY leave_type ENUM(
                    'absent',
                    'sick_leave',
                    'vacation_leave',
                    'emergency_leave',
                    'official_business',
                    'other'
                ) NOT NULL DEFAULT 'other'
            ");
        }
    }

    public function down(): void
    {
        foreach ($this->connections as $conn) {
            if (! Schema::connection($conn)->hasTable('teacher_leave_requests')) {
                continue;
            }

            DB::connection($conn)->statement("
                ALTER TABLE teacher_leave_requests
                MODIFY leave_type ENUM(
                    'sick_leave',
                    'vacation_leave',
                    'emergency_leave',
                    'official_business',
                    'other'
                ) NOT NULL DEFAULT 'other'
            ");
        }
    }
};

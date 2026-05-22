<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $connections = ['mysql_jh', 'mysql_gs'];

    public function up(): void
    {
        foreach ($this->connections as $conn) {
            if (! Schema::connection($conn)->hasTable('teacher_requests')) {
                continue;
            }

            Schema::connection($conn)->table('teacher_requests', function (Blueprint $table) use ($conn) {
                if (! Schema::connection($conn)->hasColumn('teacher_requests', 'date_from')) {
                    $table->date('date_from')->nullable()->after('preferred_end_time');
                }
                if (! Schema::connection($conn)->hasColumn('teacher_requests', 'date_to')) {
                    $table->date('date_to')->nullable()->after('date_from');
                }
            });

            DB::connection($conn)->statement("
                ALTER TABLE teacher_requests
                MODIFY request_type ENUM(
                    'time_change',
                    'room_change',
                    'teacher_reassignment',
                    'section_change',
                    'absent',
                    'sick_leave',
                    'vacation_leave',
                    'emergency_leave',
                    'official_business',
                    'leave_other',
                    'other'
                ) NOT NULL DEFAULT 'other'
            ");
        }
    }

    public function down(): void
    {
        foreach ($this->connections as $conn) {
            if (! Schema::connection($conn)->hasTable('teacher_requests')) {
                continue;
            }

            Schema::connection($conn)->table('teacher_requests', function (Blueprint $table) use ($conn) {
                if (Schema::connection($conn)->hasColumn('teacher_requests', 'date_to')) {
                    $table->dropColumn('date_to');
                }
                if (Schema::connection($conn)->hasColumn('teacher_requests', 'date_from')) {
                    $table->dropColumn('date_from');
                }
            });

            DB::connection($conn)->statement("
                ALTER TABLE teacher_requests
                MODIFY request_type ENUM(
                    'time_change',
                    'room_change',
                    'teacher_reassignment',
                    'section_change',
                    'other'
                ) NOT NULL DEFAULT 'other'
            ");
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

            if (! Schema::connection($conn)->hasColumn('teacher_leave_requests', 'submitted_to_admin')) {
                Schema::connection($conn)->table('teacher_leave_requests', function (Blueprint $table) {
                    $table->string('submitted_to_admin', 20)->nullable()->after('teacher_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->connections as $conn) {
            if (! Schema::connection($conn)->hasColumn('teacher_leave_requests', 'submitted_to_admin')) {
                continue;
            }

            Schema::connection($conn)->table('teacher_leave_requests', function (Blueprint $table) {
                $table->dropColumn('submitted_to_admin');
            });
        }
    }
};

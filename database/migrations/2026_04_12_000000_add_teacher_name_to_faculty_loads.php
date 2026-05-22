<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add teacher_name to faculty_loads in both school databases.
     * The users table lives on the default (shared) connection while
     * faculty_loads lives on mysql_jh / mysql_gs, so we store the
     * name directly to avoid a cross-database join every page load.
     */
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (!Schema::connection($connection)->hasColumn('faculty_loads', 'teacher_name')) {
                Schema::connection($connection)->table('faculty_loads', function (Blueprint $table) {
                    $table->string('teacher_name')->nullable()->after('faculty_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (Schema::connection($connection)->hasColumn('faculty_loads', 'teacher_name')) {
                Schema::connection($connection)->table('faculty_loads', function (Blueprint $table) {
                    $table->dropColumn('teacher_name');
                });
            }
        }
    }
};

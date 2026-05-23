<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('mysql')->hasTable('users')
            && ! Schema::connection('mysql')->hasColumn('users', 'shared_teacher_subjects')) {
            Schema::connection('mysql')->table('users', function (Blueprint $table) {
                $table->json('shared_teacher_subjects')->nullable()->after('school_level');
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('mysql')->hasColumn('users', 'shared_teacher_subjects')) {
            Schema::connection('mysql')->table('users', function (Blueprint $table) {
                $table->dropColumn('shared_teacher_subjects');
            });
        }
    }
};

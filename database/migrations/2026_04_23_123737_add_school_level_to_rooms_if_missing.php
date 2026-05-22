<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add school_level to the default-connection rooms table if not already present.
        // (The 2026_04_09 migration was recorded as run but the column is absent,
        //  likely because the table was dropped/recreated after that batch.)
        if (Schema::hasTable('rooms') && !Schema::hasColumn('rooms', 'school_level')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->enum('school_level', ['grade_school', 'junior_high'])
                      ->default('junior_high')
                      ->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('rooms') && Schema::hasColumn('rooms', 'school_level')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->dropColumn('school_level');
            });
        }
    }
};

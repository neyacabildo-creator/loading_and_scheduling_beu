<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            DB::connection($connection)->statement(
                'ALTER TABLE faculty_loads MODIFY COLUMN faculty_id BIGINT UNSIGNED NULL'
            );
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            DB::connection($connection)->statement(
                'ALTER TABLE faculty_loads MODIFY COLUMN faculty_id BIGINT UNSIGNED NOT NULL'
            );
        }
    }
};

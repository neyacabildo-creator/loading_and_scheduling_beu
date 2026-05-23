<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Previously dropped room_number and related columns from mysql_gs by mistake.
 * No-op: columns are required by Room model and admin UI.
 *
 * @see 2026_05_25_000000_restore_grade_school_room_columns.php for Cloud DBs that already ran the old version.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Intentionally empty.
    }

    public function down(): void
    {
        // Intentionally empty.
    }
};

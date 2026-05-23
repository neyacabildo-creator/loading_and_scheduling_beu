<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove DSS recommendation tables (feature removed from the application).
 */
return new class extends Migration
{
  /** @var list<string> */
    private array $connections = ['mysql', 'mysql_jh', 'mysql_gs'];

    public function up(): void
    {
        foreach ($this->connections as $conn) {
            if (! Schema::connection($conn)->hasTable('dss_recommendations')) {
                continue;
            }

            try {
                DB::connection($conn)->statement('DROP TRIGGER IF EXISTS `dss_recommendations_after_insert`');
                DB::connection($conn)->statement('DROP TRIGGER IF EXISTS `dss_recommendations_after_update`');
                DB::connection($conn)->statement('DROP TRIGGER IF EXISTS `dss_recommendations_after_delete`');
            } catch (\Throwable $e) {
                // triggers may not exist on this connection
            }

            Schema::connection($conn)->dropIfExists('dss_recommendations');
        }
    }

    public function down(): void
    {
        // DSS feature removed — do not recreate tables.
    }
};

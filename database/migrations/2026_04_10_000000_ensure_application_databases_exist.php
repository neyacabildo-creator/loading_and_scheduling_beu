<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Creates school-level and principal MySQL databases before migrations
 * that target mysql_jh, mysql_gs, mysql_*_teacher, or mysql_principal.
 *
 * Drops and recreates each secondary database so `migrate:fresh` (which only
 * wipes the default DB) does not leave stale tables on Laravel Cloud.
 */
return new class extends Migration
{
    public function up(): void
    {
        $defaultDatabase = config('database.connections.'.config('database.default').'.database');

        $databases = array_unique(array_filter([
            config('database.connections.mysql_jh.database'),
            config('database.connections.mysql_gs.database'),
            config('database.connections.mysql_jh_teacher.database'),
            config('database.connections.mysql_gs_teacher.database'),
            config('database.connections.mysql_principal.database'),
        ]));

        foreach ($databases as $database) {
            if ($database === $defaultDatabase) {
                continue;
            }

            $escaped = str_replace('`', '``', $database);
            DB::statement("DROP DATABASE IF EXISTS `{$escaped}`");
            DB::statement("CREATE DATABASE `{$escaped}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    }

    public function down(): void
    {
        // Do not drop databases automatically — destructive on shared clusters.
    }
};

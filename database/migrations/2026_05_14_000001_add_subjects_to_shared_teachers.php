<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a 'subjects' (JSON) column to shared_teachers in both JH and GS databases.
 * Stores up to 2 subjects that the shared teacher handles (e.g. ["Science","MAPEH"]).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (Schema::connection($connection)->hasTable('shared_teachers')
                && !Schema::connection($connection)->hasColumn('shared_teachers', 'subjects')) {
                Schema::connection($connection)->table('shared_teachers', function (Blueprint $table) {
                    // Stored as JSON array of subject strings, max 2 entries
                    $table->json('subjects')->nullable()->after('department');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (Schema::connection($connection)->hasColumn('shared_teachers', 'subjects')) {
                Schema::connection($connection)->table('shared_teachers', function (Blueprint $table) {
                    $table->dropColumn('subjects');
                });
            }
        }
    }
};

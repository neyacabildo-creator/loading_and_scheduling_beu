<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Restore essential room columns on mysql_gs after 2026_05_20_000000
 * incorrectly dropped them. Junior High (mysql_jh) was not affected.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('mysql_gs')->hasTable('rooms')) {
            return;
        }

        Schema::connection('mysql_gs')->table('rooms', function (Blueprint $table) {
            if (! Schema::connection('mysql_gs')->hasColumn('rooms', 'room_number')) {
                $table->string('room_number', 50)->nullable()->unique()->after('id');
            }
            if (! Schema::connection('mysql_gs')->hasColumn('rooms', 'building')) {
                $table->string('building')->nullable()->after('room_number');
            }
            if (! Schema::connection('mysql_gs')->hasColumn('rooms', 'has_laboratory')) {
                $table->boolean('has_laboratory')->default(false)->after('capacity');
            }
            if (! Schema::connection('mysql_gs')->hasColumn('rooms', 'has_projector')) {
                $table->boolean('has_projector')->default(true)->after('has_laboratory');
            }
            if (! Schema::connection('mysql_gs')->hasColumn('rooms', 'has_ac')) {
                $table->boolean('has_ac')->default(true)->after('has_projector');
            }
        });
    }

    public function down(): void
    {
        // Keep columns — required by the application.
    }
};

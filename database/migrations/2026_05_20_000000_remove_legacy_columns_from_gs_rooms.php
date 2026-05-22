<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop legacy columns from the Grade School rooms table.
     * Run only against mysql_gs connection.
     */
    public function up(): void
    {
        Schema::connection('mysql_gs')->table('rooms', function (Blueprint $table) {
            $columns = ['room_number', 'building', 'has_laboratory', 'has_projector', 'has_ac'];
            $existing = array_filter($columns, fn ($col) =>
                Schema::connection('mysql_gs')->hasColumn('rooms', $col)
            );
            if ($existing) {
                $table->dropColumn(array_values($existing));
            }
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_gs')->table('rooms', function (Blueprint $table) {
            $table->string('room_number', 50)->nullable()->unique()->after('id');
            $table->string('building')->nullable()->after('room_number');
            $table->boolean('has_laboratory')->default(false)->after('capacity');
            $table->boolean('has_projector')->default(true)->after('has_laboratory');
            $table->boolean('has_ac')->default(true)->after('has_projector');
        });
    }
};

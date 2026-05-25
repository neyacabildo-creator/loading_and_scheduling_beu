<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (! Schema::connection($connection)->hasTable('shared_teacher_requests')) {
                continue;
            }

            Schema::connection($connection)->table('shared_teacher_requests', function (Blueprint $table) use ($connection) {
                if (! Schema::connection($connection)->hasColumn('shared_teacher_requests', 'is_peer_mirror')) {
                    $table->boolean('is_peer_mirror')->default(false)->after('pair_key');
                }
            });

            // Legacy paired rows: mark cross-division copies created for dual approval.
            if (Schema::connection($connection)->hasColumn('shared_teacher_requests', 'pair_key')) {
                DB::connection($connection)->table('shared_teacher_requests')
                    ->whereNotNull('pair_key')
                    ->where('school_level', $connection === 'mysql_jh' ? 'grade_school' : 'junior_high')
                    ->update(['is_peer_mirror' => true]);
            }
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (! Schema::connection($connection)->hasTable('shared_teacher_requests')) {
                continue;
            }

            Schema::connection($connection)->table('shared_teacher_requests', function (Blueprint $table) use ($connection) {
                if (Schema::connection($connection)->hasColumn('shared_teacher_requests', 'is_peer_mirror')) {
                    $table->dropColumn('is_peer_mirror');
                }
            });
        }
    }
};

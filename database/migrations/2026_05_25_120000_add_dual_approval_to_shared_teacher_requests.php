<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
                if (! Schema::connection($connection)->hasColumn('shared_teacher_requests', 'pair_key')) {
                    $table->uuid('pair_key')->nullable()->index();
                }
                if (! Schema::connection($connection)->hasColumn('shared_teacher_requests', 'jh_approved_at')) {
                    $table->timestamp('jh_approved_at')->nullable();
                }
                if (! Schema::connection($connection)->hasColumn('shared_teacher_requests', 'gs_approved_at')) {
                    $table->timestamp('gs_approved_at')->nullable();
                }
                if (! Schema::connection($connection)->hasColumn('shared_teacher_requests', 'jh_approved_by')) {
                    $table->unsignedBigInteger('jh_approved_by')->nullable();
                }
                if (! Schema::connection($connection)->hasColumn('shared_teacher_requests', 'gs_approved_by')) {
                    $table->unsignedBigInteger('gs_approved_by')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (! Schema::connection($connection)->hasTable('shared_teacher_requests')) {
                continue;
            }

            Schema::connection($connection)->table('shared_teacher_requests', function (Blueprint $table) use ($connection) {
                foreach (['pair_key', 'jh_approved_at', 'gs_approved_at', 'jh_approved_by', 'gs_approved_by'] as $col) {
                    if (Schema::connection($connection)->hasColumn('shared_teacher_requests', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

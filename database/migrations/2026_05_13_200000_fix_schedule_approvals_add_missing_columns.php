<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds missing columns to the pre-existing schedule_approvals table in both
 * JH and GS school databases. The original migration was guarded by
 * hasTable(), so tables created beforehand lack admin_notes, submission_notes,
 * and revision_count — causing trigger failures on INSERT.
 *
 * Applied to: mysql_jh, mysql_gs
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            if (!Schema::connection($conn)->hasTable('schedule_approvals')) {
                continue;
            }

            Schema::connection($conn)->table('schedule_approvals', function (Blueprint $table) use ($conn) {
                if (!Schema::connection($conn)->hasColumn('schedule_approvals', 'reviewed_by')) {
                    $table->unsignedBigInteger('reviewed_by')->nullable()->after('status');
                }
                if (!Schema::connection($conn)->hasColumn('schedule_approvals', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
                }
                if (!Schema::connection($conn)->hasColumn('schedule_approvals', 'admin_notes')) {
                    $table->text('admin_notes')->nullable()->after('reviewed_at');
                }
                if (!Schema::connection($conn)->hasColumn('schedule_approvals', 'submission_notes')) {
                    $table->text('submission_notes')->nullable()->after('admin_notes');
                }
                if (!Schema::connection($conn)->hasColumn('schedule_approvals', 'revision_count')) {
                    $table->unsignedSmallInteger('revision_count')->default(0)->after('submission_notes');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            if (!Schema::connection($conn)->hasTable('schedule_approvals')) {
                continue;
            }

            Schema::connection($conn)->table('schedule_approvals', function (Blueprint $table) {
                $table->dropColumn(['admin_notes', 'submission_notes', 'revision_count']);
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates additional tables in the Principal database:
 *   • schedule_approval_logs  — central record of every principal approval decision
 *   • system_logs             — institution-wide events logged by the Principal
 *
 * The permission_requests table already exists (created in a prior migration).
 * Users live in the main DB; a snapshot view is not needed since the super
 * admin reads them directly via the mysql connection.
 *
 * Applied to: mysql_principal (loading_scheduling_principal)
 */
return new class extends Migration
{
    protected $connection = 'mysql_principal';

    public function up(): void
    {
        // ── 1. Schedule Approval Logs ────────────────────────────────────────
        // Tracks every decision the Principal (principal) makes on admin-approved
        // schedules before they are fully confirmed.
        if (!Schema::connection('mysql_principal')->hasTable('schedule_approval_logs')) {
            Schema::connection('mysql_principal')->create('schedule_approval_logs', function (Blueprint $table) {
                $table->id();

                // Which school the schedule belongs to
                $table->enum('school_level', ['junior_high', 'grade_school']);

                // The schedule_id from the respective school DB
                $table->unsignedBigInteger('schedule_id');

                // Quick denormalized snapshot so the log stays readable even after DB changes
                $table->string('faculty_name')->nullable();
                $table->string('subject')->nullable();
                $table->string('grade_level')->nullable();
                $table->string('section_name')->nullable();
                $table->string('day_of_week')->nullable();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();

                // Who approved at admin level
                $table->unsignedBigInteger('admin_approved_by')->nullable();
                $table->timestamp('admin_approved_at')->nullable();

                // Principal decision
                $table->enum('decision', ['approved', 'rejected'])->default('approved');
                $table->unsignedBigInteger('principal_id')->nullable();   // User id
                $table->string('principal_name')->nullable();             // Snapshot
                $table->text('notes')->nullable();                          // Optional remarks
                $table->timestamp('decided_at')->nullable();

                $table->timestamps();

                $table->index(['school_level', 'schedule_id']);
                $table->index('principal_id');
                $table->index('decided_at');
            });
        }

        // ── 2. System Logs ───────────────────────────────────────────────────
        // Institution-wide events logged at the principal level (login history,
        // permission decisions, bulk actions, etc.)
        // Drop first in case a prior failed run left the table in a partial state.
        Schema::connection('mysql_principal')->dropIfExists('system_logs');
        Schema::connection('mysql_principal')->create('system_logs', function (Blueprint $table) {
                $table->id();

                // Who triggered the event
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_name', 150)->nullable();       // Snapshot
                $table->string('user_role', 60)->nullable();        // Snapshot

                // Event metadata — keep short to avoid index key-length limit
                $table->string('event_type', 100);
                $table->enum('school_level', ['junior_high', 'grade_school', 'system'])->default('system');
                $table->string('target_type', 100)->nullable();     // Model / entity affected
                $table->unsignedBigInteger('target_id')->nullable();
                $table->text('description')->nullable();            // Human-readable summary

                // Extra context as JSON (old/new values, request data, etc.)
                $table->json('context')->nullable();

                // Severity for filtering
                $table->enum('severity', ['info', 'warning', 'error'])->default('info');

                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();

                $table->timestamp('occurred_at')->useCurrent();
                $table->timestamps();

                $table->index('user_id');
                $table->index('event_type');
                $table->index(['school_level', 'occurred_at'], 'sl_school_occurred_idx');
                $table->index('severity');
            });
    }

    public function down(): void
    {
        Schema::connection('mysql_principal')->dropIfExists('schedule_approval_logs');
        Schema::connection('mysql_principal')->dropIfExists('system_logs');
    }
};

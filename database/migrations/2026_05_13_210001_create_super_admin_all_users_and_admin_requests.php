<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds two tables to the Principal database:
 *
 *   • all_users        — snapshot/mirror of all system users for Principal
 *                        reference without cross-DB joins.  Synced on login
 *                        and user changes via the main app.
 *
 *   • admin_requests   — log of requests submitted by admins to the principal
 *                        (separate from teacher permission_requests).
 *                        Examples: request to unlock a schedule period, bulk
 *                        approve override, etc.
 */
return new class extends Migration
{
    protected $connection = 'mysql_principal';

    public function up(): void
    {
        // ── 1. All Users snapshot ────────────────────────────────────────────
        if (!Schema::connection('mysql_principal')->hasTable('all_users')) {
            Schema::connection('mysql_principal')->create('all_users', function (Blueprint $table) {
                $table->unsignedBigInteger('id')->primary();   // mirrors main DB user id

                $table->string('name', 150);
                $table->string('email', 150)->unique();
                $table->string('role', 60)->nullable();        // role name snapshot
                $table->enum('school_level', ['junior_high', 'grade_school', 'system'])->default('system');
                $table->boolean('is_active')->default(true);

                $table->timestamp('last_login_at')->nullable();
                $table->timestamp('synced_at')->nullable();    // when this row was last updated
                $table->timestamps();

                $table->index('role');
                $table->index('is_active');
                $table->index('school_level');
            });
        }

        // ── 2. Admin Requests ────────────────────────────────────────────────
        // Requests raised by school-level admins and directed to the principal
        // (Principal). Different from teacher permission_requests.
        if (!Schema::connection('mysql_principal')->hasTable('admin_requests')) {
            Schema::connection('mysql_principal')->create('admin_requests', function (Blueprint $table) {
                $table->id();

                // Who made the request
                $table->unsignedBigInteger('admin_id');
                $table->string('admin_name', 150)->nullable();    // snapshot
                $table->enum('school_level', ['junior_high', 'grade_school']);

                // What they are requesting
                $table->string('request_type', 100);              // e.g. 'schedule_override', 'period_unlock'
                $table->text('description')->nullable();
                $table->json('payload')->nullable();               // arbitrary request data

                // Status tracking
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->unsignedBigInteger('reviewed_by')->nullable();   // Principal user id
                $table->string('reviewed_by_name', 150)->nullable();     // snapshot
                $table->text('reviewer_notes')->nullable();
                $table->timestamp('reviewed_at')->nullable();

                // Optional link back to a specific entity
                $table->string('target_type', 100)->nullable();   // e.g. 'class_schedule'
                $table->unsignedBigInteger('target_id')->nullable();

                $table->timestamps();

                $table->index('admin_id');
                $table->index('status');
                $table->index('school_level');
                $table->index('request_type');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('mysql_principal')->dropIfExists('admin_requests');
        Schema::connection('mysql_principal')->dropIfExists('all_users');
    }
};

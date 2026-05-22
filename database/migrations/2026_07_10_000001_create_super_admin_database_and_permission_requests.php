<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the dedicated principal database and moves permission_requests there.
 *
 * Before: permission_requests lives in loading_scheduling (main DB)
 * After:  permission_requests lives in loading_scheduling_principal
 *
 * Both JH and GS admins write to the Principal DB when submitting requests.
 * Principal reads/manages all requests from the same Principal DB.
 * The school_level column identifies which school the request originated from.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Create the principal database if it doesn't exist
        $superAdminDb = config('database.connections.mysql_principal.database');
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$superAdminDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // 2. Create permission_requests in the principal database
        //    Foreign keys are omitted intentionally — requester_id/reviewed_by reference
        //    the main DB's users table, which cannot be enforced across databases.
        if (!Schema::connection('mysql_principal')->hasTable('permission_requests')) {
            Schema::connection('mysql_principal')->create('permission_requests', function (Blueprint $table) {
                $table->id();

                // Who is asking (references main DB users table — no FK constraint across DBs)
                $table->unsignedBigInteger('requester_id');

                // Who (optionally) handled the request
                $table->unsignedBigInteger('reviewed_by')->nullable();

                // Request details
                $table->string('action_type', 100);
                $table->string('subject')->nullable();
                $table->text('details');
                $table->string('school_level', 50)->nullable(); // 'junior_high' | 'grade_school'

                // Optional reference to the affected record
                $table->string('related_model', 100)->nullable();
                $table->unsignedBigInteger('related_id')->nullable();

                // Status: pending | approved | rejected | cancelled
                $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');

                $table->text('reviewer_notes')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['requester_id', 'status']);
                $table->index('status');
                $table->index('school_level');
            });
        }

        // 3. Migrate existing data from main DB to principal DB (if any exist)
        if (Schema::hasTable('permission_requests')) {
            $rows = DB::table('permission_requests')->get();
            if ($rows->isNotEmpty()) {
                DB::connection('mysql_principal')
                    ->table('permission_requests')
                    ->insertOrIgnore($rows->map(fn($r) => (array)$r)->toArray());
            }

            // 4. Drop permission_requests from main DB now that data is moved
            Schema::dropIfExists('permission_requests');
        }
    }

    public function down(): void
    {
        // Restore permission_requests in the main DB
        if (!Schema::hasTable('permission_requests')) {
            Schema::create('permission_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('requester_id');
                $table->foreign('requester_id')->references('id')->on('users')->onDelete('cascade');
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
                $table->string('action_type', 100);
                $table->string('subject')->nullable();
                $table->text('details');
                $table->string('school_level', 50)->nullable();
                $table->string('related_model', 100)->nullable();
                $table->unsignedBigInteger('related_id')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
                $table->text('reviewer_notes')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->index(['requester_id', 'status']);
                $table->index('status');
            });

            // Restore data from principal DB back to main DB
            $rows = DB::connection('mysql_principal')->table('permission_requests')->get();
            if ($rows->isNotEmpty()) {
                DB::table('permission_requests')
                    ->insertOrIgnore($rows->map(fn($r) => (array)$r)->toArray());
            }
        }

        // Drop principal permission_requests
        Schema::connection('mysql_principal')->dropIfExists('permission_requests');
    }
};

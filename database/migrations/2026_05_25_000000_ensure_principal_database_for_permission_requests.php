<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures permission_requests uses loading_scheduling_principal (not a users copy).
 * Users remain on the main mysql connection only.
 */
return new class extends Migration
{
    public function up(): void
    {
        $principalDb = config('database.connections.mysql_principal.database', 'loading_scheduling_principal');

        DB::statement("CREATE DATABASE IF NOT EXISTS `{$principalDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        if (! Schema::connection('mysql_principal')->hasTable('permission_requests')) {
            Schema::connection('mysql_principal')->create('permission_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('requester_id');
                $table->unsignedBigInteger('reviewed_by')->nullable();
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
                $table->index('school_level');
            });
        }
    }

    public function down(): void
    {
        // Keep principal DB on rollback — data may still be needed.
    }
};

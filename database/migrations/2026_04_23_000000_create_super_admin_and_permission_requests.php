<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add principal role if it doesn't exist
        DB::table('roles')->insertOrIgnore([
            'name'         => 'principal',
            'display_name' => 'Principalistrator',
            'description'  => 'Principal / Secretary — full system control over both school levels. Can approve or reject admin permission requests.',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Create permission_requests table (admins → Principals)
        Schema::create('permission_requests', function (Blueprint $table) {
            $table->id();

            // Who is asking
            $table->unsignedBigInteger('requester_id');
            $table->foreign('requester_id')->references('id')->on('users')->onDelete('cascade');

            // Who (optionally) handled the request
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');

            // Request details
            $table->string('action_type', 100);   // e.g. 'delete_schedule', 'override_load', 'bulk_approve'
            $table->string('subject')->nullable(); // Short one-line summary
            $table->text('details');               // Full description of what the admin wants to do
            $table->string('school_level', 50)->nullable();

            // Optional reference to the affected record
            $table->string('related_model', 100)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();

            // Status: pending | approved | rejected | cancelled
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');

            $table->text('reviewer_notes')->nullable(); // Principal feedback / tip
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['requester_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_requests');

        DB::table('roles')->where('name', 'principal')->delete();
    }
};

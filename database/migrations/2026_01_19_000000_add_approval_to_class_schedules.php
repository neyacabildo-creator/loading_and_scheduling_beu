<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            // Add approval-related columns
            $table->boolean('admin_approved')->default(false)->after('status');
            $table->timestamp('approved_at')->nullable()->after('admin_approved');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('approved_at');
            
            // Add columns for tracking edits
            $table->integer('version')->default(1)->after('approved_by');
            $table->text('change_log')->nullable()->after('version');
            $table->timestamp('last_modified_by_admin')->nullable()->after('change_log');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropColumn([
                'admin_approved',
                'approved_at',
                'approved_by',
                'version',
                'change_log',
                'last_modified_by_admin',
            ]);
        });
    }
};

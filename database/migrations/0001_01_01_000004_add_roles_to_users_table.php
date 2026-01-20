<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('id');
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('position')->nullable()->after('last_name');
            $table->string('school_level')->nullable()->after('position');
            $table->boolean('is_active')->default(true)->after('school_level');
            
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
            $table->dropColumn('first_name');
            $table->dropColumn('last_name');
            $table->dropColumn('position');
            $table->dropColumn('school_level');
            $table->dropColumn('is_active');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'active_session_at')) {
                $table->timestamp('active_session_at')->nullable()->after('active_session_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'active_session_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('active_session_at');
        });
    }
};

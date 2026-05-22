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
            if (! Schema::hasColumn('users', 'active_session_id')) {
                $table->string('active_session_id', 191)->nullable()->after('remember_token');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'active_session_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('active_session_id');
        });
    }
};

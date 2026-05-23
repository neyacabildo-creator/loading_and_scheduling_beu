<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('mysql_principal')->hasTable('permission_requests')
            && Schema::connection('mysql_principal')->hasColumn('permission_requests', 'subject')) {
            Schema::connection('mysql_principal')->table('permission_requests', function (Blueprint $table) {
                $table->dropColumn('subject');
            });
        }

        if (Schema::hasTable('permission_requests')
            && Schema::hasColumn('permission_requests', 'subject')) {
            Schema::table('permission_requests', function (Blueprint $table) {
                $table->dropColumn('subject');
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('mysql_principal')->hasTable('permission_requests')
            && ! Schema::connection('mysql_principal')->hasColumn('permission_requests', 'subject')) {
            Schema::connection('mysql_principal')->table('permission_requests', function (Blueprint $table) {
                $table->string('subject')->nullable()->after('action_type');
            });
        }

        if (Schema::hasTable('permission_requests')
            && ! Schema::hasColumn('permission_requests', 'subject')) {
            Schema::table('permission_requests', function (Blueprint $table) {
                $table->string('subject')->nullable()->after('action_type');
            });
        }
    }
};

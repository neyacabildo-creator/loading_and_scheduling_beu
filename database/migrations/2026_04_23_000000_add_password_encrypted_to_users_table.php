<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'password_encrypted')) {
                // BLOB stores the binary output of MySQL's AES_ENCRYPT().
                // Decrypt in SQL: SELECT AES_DECRYPT(password_encrypted, 'spup_ict_2026') AS plain_password FROM users;
                $table->binary('password_encrypted')->nullable()->after('password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'password_encrypted')) {
                $table->dropColumn('password_encrypted');
            }
        });
    }
};

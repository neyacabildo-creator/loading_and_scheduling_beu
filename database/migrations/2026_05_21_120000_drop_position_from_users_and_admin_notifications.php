<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('mysql')->hasColumn('users', 'position')) {
            Schema::connection('mysql')->table('users', function (Blueprint $table) {
                $table->dropColumn('position');
            });
        }

        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            if (! Schema::connection($conn)->hasTable('admin_notifications')) {
                Schema::connection($conn)->create('admin_notifications', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('admin_user_id');
                    $table->string('type', 60)->default('general');
                    $table->string('title', 200);
                    $table->text('message');
                    $table->string('related_type', 60)->nullable();
                    $table->unsignedBigInteger('related_id')->nullable();
                    $table->boolean('is_read')->default(false);
                    $table->timestamp('read_at')->nullable();
                    $table->unsignedBigInteger('sent_by')->nullable();
                    $table->timestamps();
                    $table->index('admin_user_id');
                    $table->index(['admin_user_id', 'is_read']);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $conn) {
            Schema::connection($conn)->dropIfExists('admin_notifications');
        }

        if (! Schema::connection('mysql')->hasColumn('users', 'position')) {
            Schema::connection('mysql')->table('users', function (Blueprint $table) {
                $table->string('position')->nullable()->after('last_name');
            });
        }
    }
};

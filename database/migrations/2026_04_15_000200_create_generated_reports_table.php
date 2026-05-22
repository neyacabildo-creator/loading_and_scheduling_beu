<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create generated_reports in both school operational databases.
     */
    public function up(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            if (!Schema::connection($connection)->hasTable('generated_reports')) {
                Schema::connection($connection)->create('generated_reports', function (Blueprint $table) {
                    $table->id();
                    $table->string('report_type', 100);
                    $table->string('format', 20)->default('csv');
                    $table->string('scope', 60);
                    $table->string('filename');
                    $table->unsignedInteger('row_count')->default(0);
                    $table->unsignedBigInteger('file_size')->nullable();
                    $table->enum('status', ['processing', 'completed', 'failed'])->default('completed');
                    $table->json('metadata')->nullable();
                    $table->unsignedBigInteger('created_by')->nullable();
                    $table->timestamps();

                    $table->index(['report_type', 'created_at']);
                    $table->index('created_by');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['mysql_jh', 'mysql_gs'] as $connection) {
            Schema::connection($connection)->dropIfExists('generated_reports');
        }
    }
};

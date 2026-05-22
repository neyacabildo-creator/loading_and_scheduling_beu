<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores feedback submitted by teachers (JH and GS) via the Provide Feedback use case.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->string('school_level', 30)->default('junior_high'); // 'junior_high' | 'grade_school'
            $table->string('category', 60); // schedule_clarity | workload_fairness | system_usability | other
            $table->tinyInteger('rating')->unsigned()->default(3); // 1-5
            $table->text('message');
            $table->string('status', 20)->default('submitted'); // submitted | reviewed | resolved
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_feedbacks');
    }
};

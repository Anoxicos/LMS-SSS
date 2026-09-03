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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enrollment_id')
                ->constrained('enrollments')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->decimal('pass_score', 5, 2)->default(0);
            $table->unsignedInteger('max_attempts')->default(1);
            $table->unsignedInteger('time_limit_minutes')->nullable();

            $table->boolean('is_final_exam')->default(false);

            $table->unsignedInteger('attempt_number')->default(1);

            $table->string('status')->default('in_progress');
            // in_progress, submitted, passed, failed, expired

            $table->decimal('score', 5, 2)->nullable();
            $table->boolean('passed')->default(false);

            $table->unsignedInteger('total_questions')->default(0);
            $table->unsignedInteger('correct_answers')->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->unsignedInteger('duration_seconds')->nullable();

            $table->index(['enrollment_id', 'attempt_number']);
            $table->index(['enrollment_id', 'status']);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};

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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->nullOnDelete();

            $table->foreignId('company_request_id')
                ->nullable()
                ->constrained('company_requests')
                ->nullOnDelete();

            $table->foreignId('current_chapter_id')
                ->nullable()
                ->constrained('chapters')
                ->nullOnDelete();

            $table->string('access_type')->default('individual');
            $table->string('status')->default('not_started');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expired_at')->nullable();

            $table->decimal('progress_percent', 5, 2)->default(0);

            $table->text('comment')->nullable();

            $table->boolean('is_approved')->default(false);
            $table->unsignedInteger('attempts_rest')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'course_id' , 'company_request_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};

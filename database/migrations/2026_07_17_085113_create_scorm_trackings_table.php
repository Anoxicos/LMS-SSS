<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scorm_trackings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enrollment_id')
                ->unique()
                ->constrained('enrollments')
                ->cascadeOnDelete();

            $table->string('scorm_version', 30)->nullable();

            /*
            |--------------------------------------------------------------------------
            | SCORM 1.2
            |--------------------------------------------------------------------------
            */
            $table->string('lesson_status', 50)->nullable();

            /*
            |--------------------------------------------------------------------------
            | SCORM 2004
            |--------------------------------------------------------------------------
            */
            $table->string('completion_status', 50)->nullable();
            $table->string('success_status', 50)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Common tracking values
            |--------------------------------------------------------------------------
            */
            $table->decimal('progress_measure', 7, 6)->nullable();
            $table->decimal('score_raw', 10, 2)->nullable();
            $table->decimal('score_min', 10, 2)->nullable();
            $table->decimal('score_max', 10, 2)->nullable();
            $table->decimal('score_scaled', 7, 6)->nullable();

            $table->text('lesson_location')->nullable();
            $table->longText('suspend_data')->nullable();

            $table->string('session_time', 100)->nullable();
            $table->string('total_time', 100)->nullable();
            $table->string('exit_status', 50)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Store every CMI value
            |--------------------------------------------------------------------------
            */
            $table->json('cmi_data')->nullable();

            $table->boolean('is_completed')
                ->default(false)
                ->index();

            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_committed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scorm_trackings');
    }
};
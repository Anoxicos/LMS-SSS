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
        Schema::create('chapter_progress', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enrollment_id')
                ->constrained('enrollments')
                ->cascadeOnDelete();

            $table->foreignId('chapter_id')
                ->constrained('chapters')
                ->cascadeOnDelete();

            $table->foreignId('last_slide_id')
                ->nullable()
                ->constrained('slides')
                ->nullOnDelete();

            $table->string('status')->default('not_started');
            $table->timestamp('started_at')->nullable();

            $table->boolean('is_completed')->default(false);
            $table->decimal('progress_percent', 5, 2)->default(0);

            $table->timestamps();

            $table->unique(['enrollment_id', 'chapter_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chapter_progress');
    }
};

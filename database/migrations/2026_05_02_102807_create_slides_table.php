
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
        Schema::create('slides', function (Blueprint $table) {
            $table->id();

            $table->foreignId('chapter_id')
                ->constrained('chapters')
                ->cascadeOnDelete();

            $table->string('title');

            /*
            |--------------------------------------------------------------------------
            | Lesson Type
            |--------------------------------------------------------------------------
            | text, video, file, link...
            */
            $table->string('lesson_type')->default('text');

            /*
            |--------------------------------------------------------------------------
            | Text Content
            |--------------------------------------------------------------------------
            */
            $table->longText('content')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Video Information
            |--------------------------------------------------------------------------
            */
            $table->string('video_url')->nullable();
            $table->unsignedInteger('video_duration')->nullable(); // seconds

            /*
            |--------------------------------------------------------------------------
            | File Information
            |--------------------------------------------------------------------------
            */
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_type')->nullable(); // pdf, docx, pptx, png...
            $table->unsignedBigInteger('file_size')->nullable(); // bytes

            /*
            |--------------------------------------------------------------------------
            | External Link
            |--------------------------------------------------------------------------
            */
            $table->string('external_url')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Slide Settings
            |--------------------------------------------------------------------------
            */
            $table->boolean('is_preview')->default(false);
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_downloadable')->default(false);
            $table->boolean('is_active')->default(true);

            /*
            |--------------------------------------------------------------------------
            | Voice Over Per Slide
            |--------------------------------------------------------------------------
            | Ici on garde seulement les infos propres au slide.
            | La langue, la voix, le provider et auto_play sont dans courses.
            */
            $table->boolean('voice_over_enabled')->default(false);
            $table->longText('voice_over_text')->nullable();

            $table->string('voice_over_audio_path')->nullable();
            $table->string('voice_over_audio_name')->nullable();
            $table->string('voice_over_audio_type')->nullable();
            $table->unsignedBigInteger('voice_over_audio_size')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['chapter_id', 'sort_order']);
            $table->index(['chapter_id', 'lesson_type']);
            $table->index(['chapter_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slides');
    }
};
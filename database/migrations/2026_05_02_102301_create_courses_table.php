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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image')->nullable();

            $table->string('type')->nullable(); // free, paid, private
            $table->string('training_type')->nullable(); // online, onsite, hybrid
            $table->string('skill_level')->nullable(); // beginner, intermediate, advanced
            $table->string('course_focus')->nullable();
            $table->string('language')->nullable();

            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 10)->default('MAD');

            $table->boolean('is_published')->default(false);

            /*
            |--------------------------------------------------------------------------
            | Global Voice Over Settings
            |--------------------------------------------------------------------------
            | Ces paramètres sont globaux pour tout le cours.
            | Les slides utilisent ces valeurs lors de la génération audio.
            */
            $table->boolean('support_voice_over')->default(false);
            $table->boolean('voice_over_auto_play')->default(false);
            $table->string('voice_over_provider')->nullable(); // example: voicerss
            $table->string('voice_over_lang', 20)->default('en-us');
            $table->string('voice_over_voice', 100)->default('default');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
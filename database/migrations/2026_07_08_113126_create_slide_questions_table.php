<?php

use App\Models\Slide;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slide_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Slide::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('question');
            $table->string('question_type', 20)->default('qcm');

            $table->string('option_a')->nullable();
            $table->string('option_b')->nullable();
            $table->string('option_c')->nullable();

            $table->string('correct_answer')->nullable();
            // a, b, c

            $table->text('explanation')->nullable();

            $table->unsignedInteger('sort_order')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slide_questions');
    }
};
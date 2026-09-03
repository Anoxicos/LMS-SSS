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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bank_id')
                ->constrained('banks')
                ->cascadeOnDelete();

            $table->string('name_chapter')->nullable();

            $table->text('question');

            $table->text('response1');
            $table->text('response2');
            $table->text('response3');
            $table->text('correct_answer');

            $table->boolean('is_modified')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};

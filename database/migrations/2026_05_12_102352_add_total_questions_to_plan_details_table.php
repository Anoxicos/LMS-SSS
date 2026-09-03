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
        Schema::table('plan_details', function (Blueprint $table) {
            $table->unsignedInteger('own_questions')->default(0)->after('name');
            $table->unsignedInteger('total_questions')->default(0)->after('own_questions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_details', function (Blueprint $table) {
            $table->dropColumn(['own_questions', 'total_questions']);
        });
    }
};

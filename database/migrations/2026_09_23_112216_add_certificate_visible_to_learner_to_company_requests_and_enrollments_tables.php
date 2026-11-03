<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_requests', function (Blueprint $table) {
            $table->boolean('certificate_visible_to_learner')
                ->default(true)
                ->after('duree_validite_formation');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->boolean('certificate_visible_to_learner')
                ->default(true)
                ->after('duree_validite_formation');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('certificate_visible_to_learner');
        });

        Schema::table('company_requests', function (Blueprint $table) {
            $table->dropColumn('certificate_visible_to_learner');
        });
    }
    
};
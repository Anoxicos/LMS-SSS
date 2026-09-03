<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('individual_request_course_id')
                ->nullable()
                ->after('company_request_id')
                ->constrained('individual_request_courses')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Une demande de cours = un seul enrollment
            |--------------------------------------------------------------------------
            */

            $table->unique(
                'individual_request_course_id',
                'enrollment_individual_request_course_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            /*
            |--------------------------------------------------------------------------
            | 1. Supprimer d'abord la clé étrangère
            |--------------------------------------------------------------------------
            |
            | MySQL interdit la suppression de l'index tant qu'il est utilisé
            | par la contrainte de clé étrangère.
            |
            */

            $table->dropForeign([
                'individual_request_course_id',
            ]);

            /*
            |--------------------------------------------------------------------------
            | 2. Supprimer ensuite l'index unique
            |--------------------------------------------------------------------------
            */

            $table->dropUnique(
                'enrollment_individual_request_course_unique'
            );

            /*
            |--------------------------------------------------------------------------
            | 3. Supprimer finalement la colonne
            |--------------------------------------------------------------------------
            */

            $table->dropColumn(
                'individual_request_course_id'
            );
        });
    }
};
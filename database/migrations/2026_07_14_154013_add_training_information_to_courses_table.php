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
        Schema::table('courses', function (Blueprint $table) {
            $table->string('ref')
                ->nullable()
                ->unique()
                ->after('category_id');

            $table->string('method')
                ->nullable()
                ->after('training_type');

            /*
             * Nombre d'heures.
             * Le type decimal permet des durées comme 1.5 ou 7.5 heures.
             */
            $table->decimal('duration_hours', 8, 2)
                ->nullable()
                ->after('method');

            /*
             * Note minimale entre 0 et 100.
             */
            $table->decimal('passing_score', 5, 2)
                ->default(0)
                ->after('duration_hours');

            /*
             * Chemin du fichier PDF enregistré dans storage.
             */
            $table->string('syllabus')
                ->nullable()
                ->after('passing_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique('courses_ref_unique');

            $table->dropColumn([
                'ref',
                'method',
                'duration_hours',
                'passing_score',
                'syllabus',
            ]);
        });
    }
};
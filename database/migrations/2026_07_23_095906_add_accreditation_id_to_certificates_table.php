<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Ajouter un index normal pour protéger la clé étrangère
        |--------------------------------------------------------------------------
        |
        | MySQL utilise actuellement l'index unique comme support de la
        | foreign key. Il faut donc ajouter un autre index avant de
        | supprimer l'index unique.
        |
        */
        Schema::table('certificates', function (Blueprint $table) {
            $table->index(
                'enrollment_id',
                'certificates_enrollment_id_index'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | 2. Supprimer l'ancienne unicité
        |--------------------------------------------------------------------------
        */
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropUnique(
                'certificates_enrollment_id_unique'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | 3. Ajouter l'agrément et les informations du certificat
        |--------------------------------------------------------------------------
        */
        Schema::table('certificates', function (Blueprint $table) {
            $table->foreignId('accreditation_id')
                ->nullable()
                ->after('enrollment_id')
                ->constrained('accreditations')
                ->restrictOnDelete();

            $table->string('issuer_name')
                ->nullable()
                ->after('name');

            $table->string('approval_reference')
                ->nullable()
                ->after('issuer_name');

            $table->string('file_path')
                ->nullable()
                ->after('approval_reference');
        });

        /*
        |--------------------------------------------------------------------------
        | 4. Un certificat unique par agrément et par inscription
        |--------------------------------------------------------------------------
        |
        | Une inscription pourra avoir :
        | - un certificat EASA ;
        | - un certificat DAC ;
        |
        | Mais elle ne pourra pas avoir deux certificats EASA.
        |
        */
        Schema::table('certificates', function (Blueprint $table) {
            $table->unique(
                [
                    'enrollment_id',
                    'accreditation_id',
                ],
                'certificates_enrollment_accreditation_unique'
            );
        });
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Supprimer l'unicité composée
        |--------------------------------------------------------------------------
        */
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropUnique(
                'certificates_enrollment_accreditation_unique'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | 2. Supprimer l'agrément et les nouveaux champs
        |--------------------------------------------------------------------------
        */
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId(
                'accreditation_id'
            );

            $table->dropColumn([
                'issuer_name',
                'approval_reference',
                'file_path',
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | 3. Restaurer l'unicité simple
        |--------------------------------------------------------------------------
        |
        | Attention : ce rollback échouera si une inscription possède
        | déjà plusieurs certificats.
        |
        */
        Schema::table('certificates', function (Blueprint $table) {
            $table->unique(
                'enrollment_id',
                'certificates_enrollment_id_unique'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | 4. Supprimer l'index normal devenu inutile
        |--------------------------------------------------------------------------
        */
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropIndex(
                'certificates_enrollment_id_index'
            );
        });
    }
};
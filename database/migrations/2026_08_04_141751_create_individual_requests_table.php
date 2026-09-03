<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('individual_requests', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Référence publique de la demande
            |--------------------------------------------------------------------------
            */

            $table->string('reference', 50)->unique();

            /*
            |--------------------------------------------------------------------------
            | Compte Learner obligatoire
            |--------------------------------------------------------------------------
            |
            | Le Learner doit créer son compte avant d'envoyer sa demande.
            | Toutes ses informations personnelles sont conservées dans users.
            |
            */

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Statut global de la demande
            |--------------------------------------------------------------------------
            |
            | pending
            | approved
            | partially_approved
            | rejected
            |
            */

            $table->string('status', 30)
                ->default('pending')
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            $table->text('learner_note')->nullable();
            $table->text('admin_note')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Validation Super Admin
            |--------------------------------------------------------------------------
            */

            $table->foreignId('reviewed_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            */

            $table->index(
                ['user_id', 'status'],
                'individual_requests_user_status_index'
            );

            $table->index(
                ['status', 'created_at'],
                'individual_requests_status_created_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('individual_requests');
    }
};
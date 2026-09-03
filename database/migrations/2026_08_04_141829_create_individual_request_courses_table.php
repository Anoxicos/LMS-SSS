<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'individual_request_courses',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('individual_request_id')
                    ->constrained('individual_requests')
                    ->cascadeOnDelete();

                $table->foreignId('course_id')
                    ->constrained('courses')
                    ->restrictOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Snapshot financier
                |--------------------------------------------------------------------------
                |
                | On conserve le prix du cours au moment de la demande.
                | Le frontend n'est jamais utilisé comme source de vérité.
                |
                */

                $table->decimal('price_snapshot', 10, 2)
                    ->default(0);

                $table->string('currency_snapshot', 10)
                    ->default('MAD');

                /*
                |--------------------------------------------------------------------------
                | Décision pour ce cours
                |--------------------------------------------------------------------------
                |
                | pending
                | approved
                | rejected
                |
                */

                $table->string('status', 30)
                    ->default('pending')
                    ->index();

                $table->text('admin_note')->nullable();

                /*
                |--------------------------------------------------------------------------
                | Informations de formation
                |--------------------------------------------------------------------------
                |
                | Ces valeurs peuvent être différentes pour chaque cours.
                |
                */

                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();

                $table->unsignedInteger('duree_validite_formation')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Validation Super Admin
                |--------------------------------------------------------------------------
                */

                $table->foreignId('reviewed_by_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('reviewed_at')->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'individual_request_id',
                        'course_id',
                    ],
                    'individual_request_course_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('individual_request_courses');
    }
};
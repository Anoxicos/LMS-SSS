<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accreditations', function (Blueprint $table) {
            $table->id();

            // easa ou dac
            $table->string('code', 30)->unique();

            // Nom visible
            $table->string('name');

            // Organisme qui délivre le certificat
            $table->string('organization_name');

            // Autorité réglementaire
            $table->string('authority_name')->nullable();

            $table->string('country')->nullable();

            // Numéro ou référence d’agrément
            $table->string('approval_reference')->nullable();

            // Logo utilisé dans le certificat
            $table->string('logo_path')->nullable();

            // Vue Blade ou modèle de certificat
            $table->string('certificate_template')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accreditations');
    }
};
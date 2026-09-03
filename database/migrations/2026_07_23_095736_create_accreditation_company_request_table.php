<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'accreditation_company_request',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('company_request_id')
                    ->constrained('company_requests')
                    ->cascadeOnDelete();

                $table->foreignId('accreditation_id')
                    ->constrained('accreditations')
                    ->restrictOnDelete();

                $table->timestamps();

                $table->unique(
                    [
                        'company_request_id',
                        'accreditation_id',
                    ],
                    'company_request_accreditation_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'accreditation_company_request'
        );
    }
};
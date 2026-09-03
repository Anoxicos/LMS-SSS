<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_requests', function (Blueprint $table) {
            $table->date('start_date')
                ->nullable()
                ->after('status');

            $table->date('end_date')
                ->nullable()
                ->after('start_date');

            $table->index(
                ['start_date', 'end_date'],
                'company_requests_training_dates_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('company_requests', function (Blueprint $table) {
            $table->dropIndex(
                'company_requests_training_dates_index'
            );

            $table->dropColumn([
                'start_date',
                'end_date',
            ]);
        });
    }
};
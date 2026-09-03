<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('exams', 'expires_at')) {
            Schema::table('exams', function (Blueprint $table) {
                $table
                    ->timestamp('expires_at')
                    ->nullable()
                    ->index()
                    ->after('started_at');
            });
        }

        if (!Schema::hasColumn('exams', 'submission_type')) {
            Schema::table('exams', function (Blueprint $table) {
                $table
                    ->string('submission_type', 30)
                    ->nullable()
                    ->after('status');
            });
        }

        if (!Schema::hasColumn('exams', 'auto_submitted_at')) {
            Schema::table('exams', function (Blueprint $table) {
                $table
                    ->timestamp('auto_submitted_at')
                    ->nullable()
                    ->after('submitted_at');
            });
        }
    }

    public function down(): void
    {
        $columns = [];

        if (Schema::hasColumn('exams', 'expires_at')) {
            $columns[] = 'expires_at';
        }

        if (Schema::hasColumn('exams', 'submission_type')) {
            $columns[] = 'submission_type';
        }

        if (Schema::hasColumn('exams', 'auto_submitted_at')) {
            $columns[] = 'auto_submitted_at';
        }

        if (!empty($columns)) {
            Schema::table('exams', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};

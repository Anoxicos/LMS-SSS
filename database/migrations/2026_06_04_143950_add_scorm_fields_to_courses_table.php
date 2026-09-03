<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('content_type')->default('normal')->after('description'); // normal, scorm

            $table->string('scorm_zip_path')->nullable()->after('content_type');
            $table->string('scorm_extract_path')->nullable()->after('scorm_zip_path');
            $table->string('scorm_entry_file')->nullable()->after('scorm_extract_path');
            $table->string('scorm_version')->nullable()->after('scorm_entry_file');
            $table->string('scorm_identifier')->nullable()->after('scorm_version');
            $table->string('scorm_title')->nullable()->after('scorm_identifier');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'content_type',
                'scorm_zip_path',
                'scorm_extract_path',
                'scorm_entry_file',
                'scorm_version',
                'scorm_identifier',
                'scorm_title',
            ]);
        });
    }
};
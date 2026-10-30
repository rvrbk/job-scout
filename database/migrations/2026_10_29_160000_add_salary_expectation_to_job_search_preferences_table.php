<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_search_preferences', function (Blueprint $table) {
            $table->unsignedInteger('salary_min')->nullable()->after('min_score');
            $table->unsignedInteger('salary_max')->nullable()->after('salary_min');
        });
    }

    public function down(): void
    {
        Schema::table('job_search_preferences', function (Blueprint $table) {
            $table->dropColumn(['salary_min', 'salary_max']);
        });
    }
};

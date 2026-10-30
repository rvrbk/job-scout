<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_search_preferences', function (Blueprint $table) {
            $table->json('boards')->nullable()->after('feeds');
        });
    }

    public function down(): void
    {
        Schema::table('job_search_preferences', function (Blueprint $table) {
            $table->dropColumn('boards');
        });
    }
};

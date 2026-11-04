<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_search_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('trigger', 16);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('fetched')->nullable();
            $table->unsignedInteger('scored')->nullable();
            $table->unsignedInteger('matched')->nullable();
            $table->json('failed_sources')->nullable();
            $table->timestamps();
        });

        Schema::table('job_postings', function (Blueprint $table) {
            $table->foreignId('job_search_run_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('job_search_run_id');
        });

        Schema::dropIfExists('job_search_runs');
    }
};

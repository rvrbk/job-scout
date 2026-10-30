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
        Schema::table('job_postings', function (Blueprint $table) {
            $table->string('source')->nullable()->after('source_url');
            $table->string('external_id')->nullable()->after('source');
            $table->string('location')->nullable()->after('company');
            $table->boolean('remote')->nullable()->after('location');
            $table->timestamp('posted_at')->nullable()->after('description');
            $table->unsignedTinyInteger('match_score')->nullable()->after('posted_at');
            $table->text('match_reason')->nullable()->after('match_score');
            $table->timestamp('matched_at')->nullable()->after('match_reason');
            $table->timestamp('dismissed_at')->nullable()->after('matched_at');

            $table->unique(['user_id', 'source', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'source', 'external_id']);
            $table->dropColumn(['source', 'external_id', 'location', 'remote', 'posted_at', 'match_score', 'match_reason', 'matched_at', 'dismissed_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->timestamp('seen_at')->nullable()->after('dismissed_at');
        });

        // Anything that already has a draft has clearly been looked at.
        DB::table('job_postings')
            ->whereExists(fn ($query) => $query->select(DB::raw(1))
                ->from('generated_documents')
                ->whereColumn('generated_documents.job_posting_id', 'job_postings.id'))
            ->update(['seen_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn('seen_at');
        });
    }
};

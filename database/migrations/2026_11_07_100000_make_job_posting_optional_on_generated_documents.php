<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A document can be written from the ledger alone, with no job posting behind it.
     */
    public function up(): void
    {
        Schema::table('generated_documents', function (Blueprint $table) {
            $table->foreignId('job_posting_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('generated_documents', function (Blueprint $table) {
            $table->foreignId('job_posting_id')->nullable(false)->change();
        });
    }
};

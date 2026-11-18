<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Documents drafted before layouts existed keep the single-column layout they were typeset in.
        Schema::table('generated_documents', function (Blueprint $table) {
            $table->string('layout')->default('classic')->after('style');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('preferred_document_layout')->nullable()->after('preferred_document_style');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('preferred_document_layout');
        });

        Schema::table('generated_documents', function (Blueprint $table) {
            $table->dropColumn('layout');
        });
    }
};

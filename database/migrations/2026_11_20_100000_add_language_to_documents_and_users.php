<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_documents', function (Blueprint $table) {
            $table->string('language')->default('en')->after('tone');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('preferred_document_language')->nullable()->after('preferred_document_tone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('preferred_document_language');
        });

        Schema::table('generated_documents', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
};

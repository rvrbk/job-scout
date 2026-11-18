<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_documents', function (Blueprint $table) {
            $table->dropColumn('layout');
            $table->string('font')->default('standard')->after('style');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('preferred_document_layout');
            $table->string('preferred_document_font')->nullable()->after('preferred_document_style');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('preferred_document_font');
            $table->string('preferred_document_layout')->nullable()->after('preferred_document_style');
        });

        Schema::table('generated_documents', function (Blueprint $table) {
            $table->dropColumn('font');
            $table->string('layout')->default('classic')->after('style');
        });
    }
};

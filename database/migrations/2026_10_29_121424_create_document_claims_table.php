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
        Schema::create('document_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generated_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ledger_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('section');
            $table->unsignedInteger('group')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->string('kind');
            $table->text('text');
            $table->string('status');
            $table->text('verifier_note')->nullable();
            $table->timestamps();
        });

        Schema::create('document_claim_ledger_fact', function (Blueprint $table) {
            $table->foreignId('document_claim_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ledger_fact_id')->constrained()->cascadeOnDelete();
            $table->primary(['document_claim_id', 'ledger_fact_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_claim_ledger_fact');
        Schema::dropIfExists('document_claims');
    }
};

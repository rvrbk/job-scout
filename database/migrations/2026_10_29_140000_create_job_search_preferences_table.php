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
        Schema::create('job_search_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('locations')->nullable();
            $table->string('remote_preference')->default('any');
            $table->json('keywords')->nullable();
            $table->json('exclusions')->nullable();
            $table->json('feeds')->nullable();
            $table->unsignedTinyInteger('min_score')->default(60);
            $table->timestamp('last_searched_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_search_preferences');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('polar_subscription_id')->nullable()->unique();
            $table->string('subscription_status')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->boolean('subscription_cancels')->default(false);
            $table->boolean('billing_exempt')->default(false);
        });

        // Everyone who exists when billing ships keeps free access.
        DB::table('users')->update(['billing_exempt' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['polar_subscription_id', 'subscription_status', 'subscription_ends_at', 'subscription_cancels', 'billing_exempt']);
        });
    }
};

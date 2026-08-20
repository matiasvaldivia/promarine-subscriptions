<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('membership_subscriptions')) {
            Schema::create('membership_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name', 100);
                $table->string('email')->index();
                $table->string('phone', 30)->nullable();
                $table->string('billing_period', 20)->default('annual');
                $table->string('status', 40)->default('pending_confirmation')->index();
                $table->json('benefits_json')->nullable();
                $table->boolean('community_updates')->default(false);
                $table->timestamp('consent_terms_at');
                $table->boolean('is_mock')->default(true);
                $table->string('source', 60)->default('standalone_membership');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_subscriptions');
    }
};

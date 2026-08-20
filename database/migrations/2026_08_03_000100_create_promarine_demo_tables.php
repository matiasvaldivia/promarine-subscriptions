<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $t) { $t->id(); $t->string('name')->unique(); $t->timestamps(); });
        }
        if (!Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $t) { $t->foreignId('role_id')->constrained()->cascadeOnDelete(); $t->foreignId('user_id')->constrained()->cascadeOnDelete(); $t->primary(['role_id','user_id']); });
        }
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('slug')->unique(); $t->text('short_description')->nullable(); $t->string('image_path')->nullable(); $t->decimal('reference_price',12,2)->default(0); $t->decimal('subscription_price',12,2)->default(0); $t->unsignedTinyInteger('saving_percent')->default(0); $t->boolean('enabled')->default(true); $t->boolean('featured')->default(false); $t->boolean('is_imported')->default(false); $t->boolean('is_mock')->default(true); $t->timestamps(); });
        }
        if (!Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $t) { $t->id(); $t->foreignId('product_id')->constrained()->cascadeOnDelete(); $t->string('name'); $t->string('sku')->nullable(); $t->string('presentation')->nullable(); $t->unsignedInteger('simulated_stock')->default(100); $t->boolean('enabled')->default(true); $t->timestamps(); });
        }
        if (!Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $t) { $t->id(); $t->foreignId('product_variant_id')->constrained(); $t->string('name'); $t->decimal('amount',12,2); $t->string('currency',3)->default('ARS'); $t->unsignedSmallInteger('frequency')->default(30); $t->string('frequency_type')->default('days'); $t->boolean('shipping_included')->default(false); $t->boolean('enabled')->default(true); $t->timestamps(); });
        }
        if (!Schema::hasTable('landing_settings')) {
            Schema::create('landing_settings', function (Blueprint $t) { $t->id(); $t->string('key')->unique(); $t->json('value_json'); $t->timestamps(); });
        }
        if (!Schema::hasTable('policy_categories')) {
            Schema::create('policy_categories', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('slug')->unique(); $t->timestamps(); });
        }
        if (!Schema::hasTable('policies')) {
            Schema::create('policies', function (Blueprint $t) { $t->id(); $t->foreignId('policy_category_id')->constrained(); $t->string('title'); $t->string('slug')->unique(); $t->string('status')->default('draft'); $t->unsignedInteger('current_version')->default(1); $t->timestamps(); });
        }
        if (!Schema::hasTable('policy_versions')) {
            Schema::create('policy_versions', function (Blueprint $t) { $t->id(); $t->foreignId('policy_id')->constrained()->cascadeOnDelete(); $t->unsignedInteger('version'); $t->longText('content'); $t->string('status')->default('draft'); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps(); $t->unique(['policy_id','version']); });
        }
        if (!Schema::hasTable('interview_sections')) {
            Schema::create('interview_sections', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('slug')->unique(); $t->unsignedInteger('position')->default(0); $t->timestamps(); });
        }
        if (!Schema::hasTable('interview_questions')) {
            Schema::create('interview_questions', function (Blueprint $t) { $t->id(); $t->foreignId('interview_section_id')->constrained()->cascadeOnDelete(); $t->text('question'); $t->text('explanation')->nullable(); $t->text('why_it_matters')->nullable(); $t->string('answer_type')->default('text'); $t->string('status')->default('pending'); $t->string('impact')->default('medium'); $t->string('responsible')->nullable(); $t->unsignedInteger('position')->default(0); $t->timestamps(); });
        }
        if (!Schema::hasTable('interview_options')) {
            Schema::create('interview_options', function (Blueprint $t) { $t->id(); $t->foreignId('interview_question_id')->constrained()->cascadeOnDelete(); $t->string('label'); $t->string('value'); $t->unsignedInteger('position')->default(0); $t->timestamps(); });
        }
        if (!Schema::hasTable('interview_answers')) {
            Schema::create('interview_answers', function (Blueprint $t) { $t->id(); $t->foreignId('interview_question_id')->constrained()->cascadeOnDelete(); $t->foreignId('user_id')->constrained(); $t->longText('answer')->nullable(); $t->text('comment')->nullable(); $t->string('status')->default('answered'); $t->timestamp('answered_at')->nullable(); $t->timestamps(); });
        }
        if (!Schema::hasTable('decision_records')) {
            Schema::create('decision_records', function (Blueprint $t) { $t->id(); $t->foreignId('interview_question_id')->nullable()->constrained()->nullOnDelete(); $t->string('title'); $t->longText('decision')->nullable(); $t->string('status')->default('pending'); $t->string('impact')->default('medium'); $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamp('approved_at')->nullable(); $t->timestamps(); });
        }
        if (!Schema::hasTable('mock_customers')) {
            Schema::create('mock_customers', function (Blueprint $t) { $t->id(); $t->uuid('uuid')->unique(); $t->string('name'); $t->string('email'); $t->string('province')->nullable(); $t->string('postal_code')->nullable(); $t->boolean('is_mock')->default(true); $t->string('environment')->default('local'); $t->timestamps(); });
        }
        if (!Schema::hasTable('mock_subscriptions')) {
            Schema::create('mock_subscriptions', function (Blueprint $t) { $t->id(); $t->uuid('uuid')->unique(); $t->foreignId('customer_id')->constrained('mock_customers'); $t->foreignId('subscription_plan_id')->constrained(); $t->string('provider')->default('mercadopago'); $t->string('provider_subscription_id')->unique(); $t->string('status')->default('draft'); $t->decimal('amount',12,2); $t->string('currency',3)->default('ARS'); $t->unsignedSmallInteger('frequency')->default(30); $t->string('frequency_type')->default('days'); $t->timestamp('next_billing_at')->nullable(); $t->timestamp('started_at')->nullable(); $t->timestamp('paused_at')->nullable(); $t->timestamp('cancelled_at')->nullable(); $t->string('campaign_code')->nullable(); $t->string('influencer_code')->nullable(); $t->boolean('is_mock')->default(true); $t->string('environment')->default('local'); $t->json('metadata_json')->nullable(); $t->timestamps(); });
        }
        if (!Schema::hasTable('mock_payments')) {
            Schema::create('mock_payments', function (Blueprint $t) { $t->id(); $t->foreignId('mock_subscription_id')->constrained()->cascadeOnDelete(); $t->string('provider_payment_id')->unique(); $t->string('status'); $t->decimal('amount',12,2); $t->string('currency',3)->default('ARS'); $t->string('idempotency_key')->unique(); $t->boolean('is_mock')->default(true); $t->string('environment')->default('local'); $t->json('payload_json')->nullable(); $t->timestamps(); });
        }
        if (!Schema::hasTable('mock_orders')) {
            Schema::create('mock_orders', function (Blueprint $t) { $t->id(); $t->foreignId('mock_subscription_id')->constrained()->cascadeOnDelete(); $t->foreignId('mock_payment_id')->unique()->constrained()->cascadeOnDelete(); $t->string('shopify_order_id')->unique(); $t->string('status'); $t->decimal('total',12,2); $t->boolean('is_mock')->default(true); $t->string('environment')->default('local'); $t->timestamps(); });
        }
        if (!Schema::hasTable('mock_order_items')) {
            Schema::create('mock_order_items', function (Blueprint $t) { $t->id(); $t->foreignId('mock_order_id')->constrained()->cascadeOnDelete(); $t->foreignId('product_variant_id')->constrained(); $t->unsignedInteger('quantity'); $t->decimal('unit_price',12,2); $t->timestamps(); });
        }
        if (!Schema::hasTable('mock_igs_events')) {
            Schema::create('mock_igs_events', function (Blueprint $t) { $t->id(); $t->foreignId('mock_order_id')->nullable()->constrained()->nullOnDelete(); $t->string('event_id')->unique(); $t->string('type'); $t->string('status')->default('recorded'); $t->decimal('commission',12,2)->nullable(); $t->boolean('is_mock')->default(true); $t->string('environment')->default('local'); $t->json('payload_json')->nullable(); $t->timestamps(); });
        }
        if (!Schema::hasTable('integration_events')) {
            Schema::create('integration_events', function (Blueprint $t) { $t->id(); $t->string('event_id')->unique(); $t->string('event_type'); $t->string('integration'); $t->string('status'); $t->boolean('is_mock')->default(true); $t->string('environment')->default('local'); $t->json('payload_json')->nullable(); $t->text('error')->nullable(); $t->timestamps(); });
        }
        if (!Schema::hasTable('asset_imports')) {
            Schema::create('asset_imports', function (Blueprint $t) { $t->id(); $t->text('original_url'); $t->string('local_path'); $t->string('type'); $t->string('sha256')->nullable(); $t->text('source_page'); $t->string('status'); $t->timestamp('imported_at')->nullable(); $t->timestamps(); });
        }
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $t) { $t->id(); $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $t->string('action'); $t->string('auditable_type')->nullable(); $t->unsignedBigInteger('auditable_id')->nullable(); $t->string('ip_address')->nullable(); $t->json('changes_json')->nullable(); $t->timestamps(); });
        }
        if (!Schema::hasTable('attachments')) {
            Schema::create('attachments', function (Blueprint $t) { $t->id(); $t->foreignId('user_id')->constrained(); $t->nullableMorphs('attachable'); $t->string('original_name'); $t->string('disk_path'); $t->string('mime_type'); $t->unsignedBigInteger('size'); $t->timestamps(); });
        }
        if (!Schema::hasTable('policy_acceptances')) {
            Schema::create('policy_acceptances', function (Blueprint $t) { $t->id(); $t->foreignId('policy_version_id')->constrained(); $t->foreignId('mock_customer_id')->constrained(); $t->timestamp('accepted_at'); $t->boolean('is_mock')->default(true); $t->string('environment')->default('local'); $t->timestamps(); });
        }
    }

    public function down(): void
    {
        foreach (['policy_acceptances','attachments','audit_logs','asset_imports','integration_events','mock_igs_events','mock_order_items','mock_orders','mock_payments','mock_subscriptions','mock_customers','decision_records','interview_answers','interview_options','interview_questions','interview_sections','policy_versions','policies','policy_categories','landing_settings','subscription_plans','product_variants','products','role_user','roles'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

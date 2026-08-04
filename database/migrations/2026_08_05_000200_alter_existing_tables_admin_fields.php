<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── products ───────────────────────────────────────────────
        Schema::table('products', function (Blueprint $t) {
            if (!Schema::hasColumn('products', 'status'))
                $t->string('status')->default('active')->after('is_mock');
            if (!Schema::hasColumn('products', 'shopify_product_id'))
                $t->string('shopify_product_id')->nullable()->after('status');
            if (!Schema::hasColumn('products', 'igs_product_id'))
                $t->string('igs_product_id')->nullable()->after('shopify_product_id');
            if (!Schema::hasColumn('products', 'light_theme_image'))
                $t->string('light_theme_image')->nullable()->after('igs_product_id');
            if (!Schema::hasColumn('products', 'dark_theme_image'))
                $t->string('dark_theme_image')->nullable()->after('light_theme_image');
            if (!Schema::hasColumn('products', 'deleted_at'))
                $t->softDeletes();
        });

        // ── product_variants ───────────────────────────────────────
        Schema::table('product_variants', function (Blueprint $t) {
            if (!Schema::hasColumn('product_variants', 'shopify_variant_id'))
                $t->string('shopify_variant_id')->nullable()->after('sku');
            if (!Schema::hasColumn('product_variants', 'external_sku'))
                $t->string('external_sku')->nullable()->after('shopify_variant_id');
            if (!Schema::hasColumn('product_variants', 'stock_policy'))
                $t->string('stock_policy')->default('track')->after('simulated_stock');
            if (!Schema::hasColumn('product_variants', 'allow_backorder'))
                $t->boolean('allow_backorder')->default(false)->after('stock_policy');
            if (!Schema::hasColumn('product_variants', 'low_stock_threshold'))
                $t->unsignedInteger('low_stock_threshold')->default(10)->after('allow_backorder');
            if (!Schema::hasColumn('product_variants', 'status'))
                $t->string('status')->default('active')->after('low_stock_threshold');
            if (!Schema::hasColumn('product_variants', 'last_synced_at'))
                $t->timestamp('last_synced_at')->nullable()->after('status');
            if (!Schema::hasColumn('product_variants', 'deleted_at'))
                $t->softDeletes();
        });

        // ── mock_customers ─────────────────────────────────────────
        Schema::table('mock_customers', function (Blueprint $t) {
            if (!Schema::hasColumn('mock_customers', 'status'))
                $t->string('status')->default('active')->after('is_mock');
            if (!Schema::hasColumn('mock_customers', 'document_type'))
                $t->string('document_type')->nullable()->after('email');
            if (!Schema::hasColumn('mock_customers', 'document_number'))
                $t->string('document_number')->nullable()->after('document_type');
            if (!Schema::hasColumn('mock_customers', 'shopify_customer_id'))
                $t->string('shopify_customer_id')->nullable()->after('status');
            if (!Schema::hasColumn('mock_customers', 'mercadopago_customer_id'))
                $t->string('mercadopago_customer_id')->nullable()->after('shopify_customer_id');
            if (!Schema::hasColumn('mock_customers', 'igs_customer_id'))
                $t->string('igs_customer_id')->nullable()->after('mercadopago_customer_id');
            if (!Schema::hasColumn('mock_customers', 'source'))
                $t->string('source')->default('wizard')->after('igs_customer_id');
            if (!Schema::hasColumn('mock_customers', 'last_synced_at'))
                $t->timestamp('last_synced_at')->nullable()->after('source');
            if (!Schema::hasColumn('mock_customers', 'deleted_at'))
                $t->softDeletes();
        });

        // ── mock_subscriptions ─────────────────────────────────────
        Schema::table('mock_subscriptions', function (Blueprint $t) {
            if (!Schema::hasColumn('mock_subscriptions', 'current_cycle'))
                $t->unsignedInteger('current_cycle')->default(0)->after('cancelled_at');
            if (!Schema::hasColumn('mock_subscriptions', 'resumed_at'))
                $t->timestamp('resumed_at')->nullable()->after('current_cycle');
            if (!Schema::hasColumn('mock_subscriptions', 'expired_at'))
                $t->timestamp('expired_at')->nullable()->after('resumed_at');
        });

        // ── mock_orders ────────────────────────────────────────────
        Schema::table('mock_orders', function (Blueprint $t) {
            if (!Schema::hasColumn('mock_orders', 'internal_status'))
                $t->string('internal_status')->default('payment_approved')->after('status');
            if (!Schema::hasColumn('mock_orders', 'financial_status'))
                $t->string('financial_status')->default('paid')->after('internal_status');
            if (!Schema::hasColumn('mock_orders', 'fulfillment_status'))
                $t->string('fulfillment_status')->default('unfulfilled')->after('financial_status');
            if (!Schema::hasColumn('mock_orders', 'transmitted_at'))
                $t->timestamp('transmitted_at')->nullable()->after('fulfillment_status');
            if (!Schema::hasColumn('mock_orders', 'confirmed_at'))
                $t->timestamp('confirmed_at')->nullable()->after('transmitted_at');
            if (!Schema::hasColumn('mock_orders', 'dispatched_at'))
                $t->timestamp('dispatched_at')->nullable()->after('confirmed_at');
            if (!Schema::hasColumn('mock_orders', 'delivered_at'))
                $t->timestamp('delivered_at')->nullable()->after('dispatched_at');
            if (!Schema::hasColumn('mock_orders', 'cancelled_at'))
                $t->timestamp('cancelled_at')->nullable()->after('delivered_at');
        });

        // ── mock_payments ──────────────────────────────────────────
        Schema::table('mock_payments', function (Blueprint $t) {
            if (!Schema::hasColumn('mock_payments', 'billing_cycle'))
                $t->unsignedInteger('billing_cycle')->default(1)->after('currency');
            if (!Schema::hasColumn('mock_payments', 'failure_reason'))
                $t->string('failure_reason')->nullable()->after('billing_cycle');
            if (!Schema::hasColumn('mock_payments', 'approved_at'))
                $t->timestamp('approved_at')->nullable()->after('failure_reason');
            if (!Schema::hasColumn('mock_payments', 'rejected_at'))
                $t->timestamp('rejected_at')->nullable()->after('approved_at');
            if (!Schema::hasColumn('mock_payments', 'refunded_at'))
                $t->timestamp('refunded_at')->nullable()->after('rejected_at');
        });

        // ── mock_igs_events ────────────────────────────────────────
        Schema::table('mock_igs_events', function (Blueprint $t) {
            if (!Schema::hasColumn('mock_igs_events', 'influencer_code'))
                $t->string('influencer_code')->nullable()->after('commission');
            if (!Schema::hasColumn('mock_igs_events', 'campaign'))
                $t->string('campaign')->nullable()->after('influencer_code');
            if (!Schema::hasColumn('mock_igs_events', 'base_amount'))
                $t->decimal('base_amount', 12, 2)->nullable()->after('campaign');
            if (!Schema::hasColumn('mock_igs_events', 'reversed_at'))
                $t->timestamp('reversed_at')->nullable()->after('base_amount');
        });

        // ── audit_logs ─────────────────────────────────────────────
        Schema::table('audit_logs', function (Blueprint $t) {
            if (!Schema::hasColumn('audit_logs', 'description'))
                $t->text('description')->nullable()->after('action');
            if (!Schema::hasColumn('audit_logs', 'user_agent'))
                $t->string('user_agent', 500)->nullable()->after('ip_address');
            if (!Schema::hasColumn('audit_logs', 'before_json'))
                $t->json('before_json')->nullable()->after('changes_json');
            if (!Schema::hasColumn('audit_logs', 'after_json'))
                $t->json('after_json')->nullable()->after('before_json');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', fn($t) => $t->dropColumn(['user_agent', 'before_json', 'after_json']));
        Schema::table('mock_igs_events', fn($t) => $t->dropColumn(['influencer_code', 'campaign', 'base_amount', 'reversed_at']));
        Schema::table('mock_payments', fn($t) => $t->dropColumn(['billing_cycle', 'failure_reason', 'approved_at', 'rejected_at', 'refunded_at']));
        Schema::table('mock_orders', fn($t) => $t->dropColumn(['internal_status', 'financial_status', 'fulfillment_status', 'transmitted_at', 'confirmed_at', 'dispatched_at', 'delivered_at', 'cancelled_at']));
        Schema::table('mock_subscriptions', fn($t) => $t->dropColumn(['current_cycle', 'resumed_at', 'expired_at']));
        Schema::table('mock_customers', fn($t) => $t->dropColumn(['status', 'document_type', 'document_number', 'shopify_customer_id', 'mercadopago_customer_id', 'igs_customer_id', 'source', 'last_synced_at', 'deleted_at']));
        Schema::table('product_variants', fn($t) => $t->dropColumn(['shopify_variant_id', 'external_sku', 'stock_policy', 'allow_backorder', 'low_stock_threshold', 'status', 'last_synced_at', 'deleted_at']));
        Schema::table('products', fn($t) => $t->dropColumn(['status', 'shopify_product_id', 'igs_product_id', 'light_theme_image', 'dark_theme_image', 'deleted_at']));
    }
};

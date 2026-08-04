<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── PERMISSIONS ────────────────────────────────────────────
        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('label')->nullable();
            $t->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $t) {
            $t->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $t->foreignId('role_id')->constrained()->cascadeOnDelete();
            $t->primary(['permission_id', 'role_id']);
        });

        // ── CUSTOMER ADDRESSES ─────────────────────────────────────
        Schema::create('customer_addresses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('mock_customer_id')->constrained()->cascadeOnDelete();
            $t->string('alias')->nullable();
            $t->string('province')->nullable();
            $t->string('locality')->nullable();
            $t->string('postal_code')->nullable();
            $t->string('address')->nullable();
            $t->string('address_number')->nullable();
            $t->string('apartment')->nullable();
            $t->string('reference')->nullable();
            $t->boolean('is_default')->default(false);
            $t->timestamps();
        });

        // ── PRODUCT SUBSCRIPTION MATRIX ───────────────────────────
        Schema::create('product_subscription_matrix', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $t->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('billing_interval_days')->default(30);
            $t->unsignedSmallInteger('minimum_cycles')->default(1);
            $t->decimal('base_price', 12, 2);
            $t->string('discount_type')->default('percentage');  // percentage|fixed
            $t->decimal('discount_value', 8, 2)->default(0);
            $t->decimal('subscription_price', 12, 2);
            $t->boolean('shipping_included')->default(false);
            $t->boolean('pause_allowed')->default(true);
            $t->boolean('cancellation_allowed')->default(true);
            $t->unsignedInteger('stock_required')->default(1);
            $t->string('status')->default('active');             // active|inactive|archived
            $t->string('external_code')->nullable();
            $t->date('valid_from')->nullable();
            $t->date('valid_until')->nullable();
            $t->json('metadata_json')->nullable();
            $t->timestamps();
            $t->unique(['product_id', 'variant_id', 'subscription_plan_id'], 'psm_unique');
        });

        // ── INVENTORY ──────────────────────────────────────────────
        Schema::create('inventory_locations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('external_id')->nullable()->unique();
            $t->string('source')->default('shopify_mock');
            $t->boolean('is_active')->default(true);
            $t->boolean('is_mock')->default(true);
            $t->timestamps();
        });

        Schema::create('inventory_levels', function (Blueprint $t) {
            $t->id();
            $t->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $t->foreignId('location_id')->constrained('inventory_locations')->cascadeOnDelete();
            $t->unsignedInteger('available_quantity')->default(0);
            $t->unsignedInteger('reserved_quantity')->default(0);
            $t->unsignedInteger('committed_quantity')->default(0);
            $t->unsignedInteger('incoming_quantity')->default(0);
            $t->string('sync_status')->default('unknown');
            // unknown|in_stock|low_stock|out_of_stock|backorder|sync_error
            $t->timestamp('last_synced_at')->nullable();
            $t->boolean('is_mock')->default(true);
            $t->string('environment')->default('local');
            $t->unique(['variant_id', 'location_id']);
            $t->timestamps();
        });

        Schema::create('inventory_reservations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $t->foreignId('location_id')->constrained('inventory_locations')->cascadeOnDelete();
            $t->unsignedInteger('quantity');
            $t->string('reference_type')->nullable();  // mock_order|mock_subscription
            $t->unsignedBigInteger('reference_id')->nullable();
            $t->string('status')->default('active');   // active|released|expired
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $t->foreignId('location_id')->constrained('inventory_locations')->cascadeOnDelete();
            $t->string('type');
            // sync|reservation|release|order_committed|fulfillment|adjustment|refund|return
            $t->integer('quantity');  // puede ser negativo
            $t->string('reference_type')->nullable();
            $t->unsignedBigInteger('reference_id')->nullable();
            $t->string('reason')->nullable();
            $t->json('metadata_json')->nullable();
            $t->timestamps();
        });

        // ── SHOPIFY SYNC ────────────────────────────────────────────
        Schema::create('shopify_sync_runs', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('entity_type');
            // products|variants|inventory|orders|fulfillments|customers
            $t->string('direction')->default('shopify_to_local');
            // shopify_to_local|local_to_shopify|bidirectional
            $t->string('status')->default('queued');
            // queued|running|completed|completed_with_errors|failed|cancelled
            $t->timestamp('started_at')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->unsignedInteger('records_read')->default(0);
            $t->unsignedInteger('records_created')->default(0);
            $t->unsignedInteger('records_updated')->default(0);
            $t->unsignedInteger('records_failed')->default(0);
            $t->string('cursor')->nullable();
            $t->text('error_message')->nullable();
            $t->json('metadata_json')->nullable();
            $t->boolean('is_mock')->default(true);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('shopify_sync_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sync_run_id')->constrained('shopify_sync_runs')->cascadeOnDelete();
            $t->string('entity_type');
            $t->unsignedBigInteger('local_id')->nullable();
            $t->string('external_id')->nullable();
            $t->string('operation')->nullable();  // create|update|skip|fail
            $t->string('status')->default('pending');
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->text('last_error')->nullable();
            $t->string('payload_hash')->nullable();
            $t->json('payload_json')->nullable();
            $t->timestamp('processed_at')->nullable();
            $t->timestamps();
        });

        // ── ORDER STATUS HISTORY ────────────────────────────────────
        Schema::create('order_status_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('mock_order_id')->constrained()->cascadeOnDelete();
            $t->string('from_status')->nullable();
            $t->string('to_status');
            $t->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('reason')->nullable();
            $t->json('metadata_json')->nullable();
            $t->timestamps();
        });

        // ── FULFILLMENTS ────────────────────────────────────────────
        Schema::create('fulfillments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('mock_order_id')->constrained()->cascadeOnDelete();
            $t->string('external_fulfillment_id')->nullable()->unique();
            $t->string('status')->default('pending');
            // pending|preparing|ready|shipped|in_transit|delivered|failed|returned
            $t->string('carrier')->nullable();
            $t->string('tracking_number')->nullable();
            $t->string('tracking_url')->nullable();
            $t->timestamp('prepared_at')->nullable();
            $t->timestamp('shipped_at')->nullable();
            $t->timestamp('delivered_at')->nullable();
            $t->timestamp('failed_at')->nullable();
            $t->text('failure_reason')->nullable();
            $t->boolean('is_mock')->default(true);
            $t->string('environment')->default('local');
            $t->json('metadata_json')->nullable();
            $t->timestamps();
        });

        // ── ADMINISTRATIVE NOTES ────────────────────────────────────
        Schema::create('administrative_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->nullableMorphs('notable');  // notable_type + notable_id
            $t->text('content');
            $t->boolean('is_pinned')->default(false);
            $t->timestamps();
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        foreach ([
            'administrative_notes',
            'fulfillments',
            'order_status_history',
            'shopify_sync_items',
            'shopify_sync_runs',
            'inventory_movements',
            'inventory_reservations',
            'inventory_levels',
            'inventory_locations',
            'product_subscription_matrix',
            'customer_addresses',
            'permission_role',
            'permissions',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

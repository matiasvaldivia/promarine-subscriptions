<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('type')->nullable()->after('presentation');
            $table->decimal('units_per_package', 10, 2)->nullable()->after('type');
            $table->string('unit_measure')->nullable()->after('units_per_package');
            $table->decimal('recommended_daily_dose', 8, 2)->nullable()->after('unit_measure');
            $table->unsignedSmallInteger('estimated_days')->nullable()->after('recommended_daily_dose');
            $table->decimal('price', 12, 2)->nullable()->after('estimated_days');
            $table->unsignedInteger('weight_grams')->nullable()->after('price');
            $table->string('image_path')->nullable()->after('weight_grams');
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedSmallInteger('minimum_cycles')->default(1)->after('frequency_type');
            $table->string('discount_type')->default('percentage')->after('minimum_cycles');
            $table->decimal('discount_value', 8, 2)->default(0)->after('discount_type');
            $table->boolean('can_pause')->default(true)->after('shipping_included');
            $table->boolean('can_cancel')->default(true)->after('can_pause');
        });

        Schema::table('mock_customers', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('locality')->nullable()->after('province');
            $table->string('address')->nullable()->after('postal_code');
            $table->string('address_number')->nullable()->after('address');
            $table->string('apartment')->nullable()->after('address_number');
            $table->string('address_reference')->nullable()->after('apartment');
        });
    }

    public function down(): void
    {
        Schema::table('mock_customers', function (Blueprint $table) {
            $table->dropColumn(['phone', 'locality', 'address', 'address_number', 'apartment', 'address_reference']);
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['minimum_cycles', 'discount_type', 'discount_value', 'can_pause', 'can_cancel']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['type', 'units_per_package', 'unit_measure', 'recommended_daily_dose', 'estimated_days', 'price', 'weight_grams', 'image_path']);
        });
    }
};

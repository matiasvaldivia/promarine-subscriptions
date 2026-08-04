<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductSubscriptionMatrix;
use App\Models\ProductVariant;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class CartMatrixSeeder extends Seeder
{
    public function run(): void
    {
        // Leer productos y planes existentes
        $products = Product::with('variants.plans')->get();

        if ($products->isEmpty()) {
            $this->command->warn('⚠ No hay productos. Ejecutá ProductSeeder primero.');
            return;
        }

        $totalCreated = 0;

        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                foreach ($variant->plans as $plan) {
                    ProductSubscriptionMatrix::updateOrCreate(
                        [
                            'product_id'           => $product->id,
                            'variant_id'           => $variant->id,
                            'subscription_plan_id' => $plan->id,
                        ],
                        [
                            'billing_interval_days' => $plan->frequency,
                            'minimum_cycles'        => $plan->minimum_cycles,
                            'base_price'            => $variant->price ?? $product->reference_price,
                            'discount_type'         => $plan->discount_type,
                            'discount_value'        => $plan->discount_value,
                            'subscription_price'    => $plan->amount,
                            'shipping_included'     => $plan->shipping_included,
                            'pause_allowed'         => $plan->can_pause,
                            'cancellation_allowed'  => $plan->can_cancel,
                            'stock_required'        => 1,
                            'status'                => 'active',
                            'external_code'         => strtoupper($variant->sku . '-' . str_replace(' ', '-', $plan->name)),
                            'valid_from'            => now()->toDateString(),
                            'valid_until'           => null,
                        ]
                    );
                    $totalCreated++;
                }
            }
        }

        $this->command->info("✓ Matriz comercial: {$totalCreated} filas (esperado: 24)");
    }
}

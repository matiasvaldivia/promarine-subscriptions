<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['Marine Epic', 'marine-epic', 'Energía y defensas para tu día.', 104000],
            ['Marine Fusion', 'marine-fusion', 'Fórmula marina para acompañar tu rutina diaria.', 99000],
            ['Echa Marine', 'echa-marine', 'Nutrición marina de origen patagónico.', 96000],
            ['Marine Pulse', 'marine-pulse', 'Propuesta diaria con tecnología marina.', 101000],
        ];

        $presentations = [
            ['Botella', 'botella', 'bottle'],
            ['Monodosis', 'monodosis', 'box'],
        ];

        $planMatrix = [
            ['Suscripción flexible', 1, 10, true, true],
            ['Plan 3 meses', 3, 12, true, false],
            ['Plan 6 meses', 6, 15, true, false],
        ];

        foreach ($items as $index => [$name, $slug, $description, $price]) {
            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'short_description' => $description,
                    'image_path' => '/assets/promarine/products/'.$slug.'-packshot-square.png',
                    'reference_price' => $price,
                    'subscription_price' => round($price * .90, 2),
                    'saving_percent' => 10,
                    'enabled' => true,
                    'featured' => $index === 0,
                    'is_imported' => true,
                    'is_mock' => true,
                ],
            );

            ProductVariant::where('product_id', $product->id)
                ->whereNotIn('name', array_column($presentations, 0))
                ->update(['enabled' => false]);

            foreach ($presentations as [$presentation, $type, $imageSuffix]) {
                $variant = ProductVariant::updateOrCreate(
                    ['product_id' => $product->id, 'name' => $presentation],
                    [
                        'sku' => 'DEMO-'.strtoupper($slug).'-'.strtoupper($type),
                        'presentation' => $presentation,
                        'type' => $type,
                        'units_per_package' => null,
                        'unit_measure' => null,
                        'recommended_daily_dose' => null,
                        'estimated_days' => null,
                        'price' => $price,
                        'weight_grams' => null,
                        'image_path' => '/assets/promarine/demo/'.$slug.'-'.$imageSuffix.'.png',
                        'simulated_stock' => 100,
                        'enabled' => true,
                    ],
                );

                SubscriptionPlan::where('product_variant_id', $variant->id)
                    ->where('name', 'Cada 30 días')
                    ->update(['name' => 'Suscripción flexible']);

                foreach ($planMatrix as [$planName, $cycles, $discount, $canPause, $canCancel]) {
                    SubscriptionPlan::updateOrCreate(
                        ['product_variant_id' => $variant->id, 'name' => $planName],
                        [
                            'amount' => round($price * (1 - ($discount / 100)), 2),
                            'currency' => 'ARS',
                            'frequency' => 30,
                            'frequency_type' => 'days',
                            'minimum_cycles' => $cycles,
                            'discount_type' => 'percentage',
                            'discount_value' => $discount,
                            'shipping_included' => false,
                            'can_pause' => $canPause,
                            'can_cancel' => $canCancel,
                            'enabled' => true,
                        ],
                    );
                }
            }
        }
    }
}

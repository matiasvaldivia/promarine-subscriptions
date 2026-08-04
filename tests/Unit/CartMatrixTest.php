<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductSubscriptionMatrix;
use App\Models\ProductVariant;
use App\Models\SubscriptionPlan;
use App\Services\CartMatrixService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del servicio de Matriz Comercial.
 *
 * Reglas críticas:
 * - La matriz debe contener exactamente 24 combinaciones (4 productos × 2 variantes × 3 planes).
 * - Editar el descuento de una fila recalcula subscription_price.
 * - El precio de suscripción no puede ser negativo (se clampea a 0 en el servicio).
 */
class CartMatrixTest extends TestCase
{
    use RefreshDatabase;

    private CartMatrixService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CartMatrixService::class);
        $this->seedMatrix();
    }

    /**
     * Crea 4 productos × 2 variantes × 3 planes = 24 filas usando factories.
     */
    private function seedMatrix(): void
    {
        $plans = [
            ['name' => 'Mensual',    'frequency' => 30,  'amount' => 1000],
            ['name' => 'Bimestral', 'frequency' => 60,  'amount' => 950],
            ['name' => 'Trimestral','frequency' => 90,  'amount' => 900],
        ];

        for ($p = 1; $p <= 4; $p++) {
            $product = Product::factory()->create();

            for ($v = 1; $v <= 2; $v++) {
                $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

                foreach ($plans as $planData) {
                    $plan = SubscriptionPlan::factory()->create([
                        'product_variant_id' => $variant->id,
                        'name'               => $planData['name'],
                        'frequency'          => $planData['frequency'],
                        'amount'             => $planData['amount'],
                    ]);

                    ProductSubscriptionMatrix::create([
                        'product_id'            => $product->id,
                        'variant_id'            => $variant->id,
                        'subscription_plan_id'  => $plan->id,
                        'billing_interval_days' => $plan->frequency,
                        'base_price'            => $product->reference_price,
                        'discount_type'         => 'percentage',
                        'discount_value'        => 10.0,
                        'subscription_price'    => $plan->amount,
                        'shipping_included'     => false,
                        'pause_allowed'         => true,
                        'cancellation_allowed'  => true,
                        'stock_required'        => 1,
                        'status'                => 'active',
                        'external_code'         => strtoupper("PROD-{$p}-VAR-{$v}-{$planData['name']}"),
                        'valid_from'            => now()->toDateString(),
                    ]);
                }
            }
        }
    }

    // ─── Conteo de combinaciones ──────────────────────────────────────────────

    /** @test */
    public function matrix_has_exactly_24_rows(): void
    {
        $count = $this->service->all()->count();
        $this->assertEquals(24, $count,
            "La matriz debe tener 4 productos × 2 variantes × 3 planes = 24 filas. Encontradas: $count");
    }

    // ─── Cálculo de precios ───────────────────────────────────────────────────

    /** @test */
    public function row_update_recalculates_subscription_price(): void
    {
        $row = $this->service->all()->first();
        $this->assertNotNull($row, 'La matriz debe tener al menos 1 fila');

        $updatedRow = $this->service->updateRow($row, [
            'discount_type'  => 'percentage',
            'discount_value' => 20.0,
        ]);

        $expectedPrice = $row->base_price * (1 - 20.0 / 100);
        $this->assertEquals(round($expectedPrice, 2), round($updatedRow->subscription_price, 2));
    }

    /** @test */
    public function subscription_price_cannot_exceed_reference_price(): void
    {
        $row = $this->service->all()->first();
        $this->assertNotNull($row);

        $updatedRow = $this->service->updateRow($row, [
            'discount_type'  => 'percentage',
            'discount_value' => 0.0,
        ]);

        $this->assertLessThanOrEqual(
            $row->base_price,
            $updatedRow->subscription_price,
            'El precio de suscripción no puede superar el precio base'
        );
    }

    /** @test */
    public function update_row_does_not_affect_other_rows_prices(): void
    {
        $rows   = $this->service->all();
        $first  = $rows->first();
        $second = $rows->skip(1)->first();

        $originalSecondPrice = $second->subscription_price;

        $this->service->updateRow($first, [
            'discount_type'  => 'percentage',
            'discount_value' => 50.0,
        ]);

        $second->refresh();
        $this->assertEquals($originalSecondPrice, $second->subscription_price,
            'Actualizar una fila no debe cambiar el precio de las otras');
    }

    /** @test */
    public function matrix_row_has_all_required_fields(): void
    {
        $row = $this->service->all()->first();
        $this->assertNotNull($row);

        $this->assertNotNull($row->product_id);
        $this->assertNotNull($row->variant_id);
        $this->assertNotNull($row->subscription_plan_id);
        $this->assertNotNull($row->base_price);
        $this->assertNotNull($row->subscription_price);
        $this->assertNotNull($row->discount_type);
    }

    /** @test */
    public function total_rows_count_equals_products_times_variants_times_plans(): void
    {
        $total = ProductSubscriptionMatrix::count();
        $this->assertEquals(4 * 2 * 3, $total);
    }
}

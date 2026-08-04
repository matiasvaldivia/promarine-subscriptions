<?php

namespace Tests\Unit;

use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\ProductVariant;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del servicio de inventario.
 *
 * API real de InventoryService:
 *   availableStock(int $variantId, int $locationId): int
 *   adjust(InventoryLevel $level, int $delta, string $reason): void
 *   reserve(int $variantId, int $locationId, int $qty, string $refType, int $refId): InventoryReservation
 *   release(InventoryReservation $reservation): void
 *   summary(): array
 *
 * Schema real:
 *   products: name, slug, short_description, reference_price, subscription_price, saving_percent, enabled, featured, is_mock
 *   product_variants: product_id, name, sku, presentation, simulated_stock, enabled
 *   inventory_locations: name, source, is_active, is_mock
 *   inventory_levels: variant_id, location_id, available_quantity, reserved_quantity, committed_quantity, incoming_quantity, sync_status, is_mock, environment
 */
class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;
    private InventoryLocation $location;
    private ProductVariant $variant;
    private InventoryLevel $level;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InventoryService::class);

        $product = Product::factory()->create();
        $this->variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        $this->location = InventoryLocation::factory()->create();

        $this->level = InventoryLevel::factory()->create([
            'location_id'        => $this->location->id,
            'variant_id'         => $this->variant->id,
            'available_quantity' => 20,
            'reserved_quantity'  => 0,
            'committed_quantity' => 0,
        ]);
    }

    // ─── availableStock ────────────────────────────────────────────────────────

    /** @test */
    public function available_stock_equals_available_minus_reserved_minus_committed(): void
    {
        $this->level->update([
            'available_quantity' => 20,
            'reserved_quantity'  => 5,
            'committed_quantity' => 2,
        ]);

        $result = $this->service->availableStock(
            $this->variant->id,
            $this->location->id
        );

        $this->assertEquals(13, $result,
            'availableStock = available(20) - reserved(5) - committed(2) = 13');
    }

    /** @test */
    public function available_stock_returns_zero_for_unknown_variant(): void
    {
        $result = $this->service->availableStock(99999, $this->location->id);
        $this->assertEquals(0, $result);
    }

    // ─── Ajuste positivo ───────────────────────────────────────────────────────

    /** @test */
    public function positive_adjust_increases_available_quantity(): void
    {
        $before = $this->level->available_quantity;
        $this->service->adjust($this->level, +10, 'Reposición test');

        $this->level->refresh();
        $this->assertEquals($before + 10, $this->level->available_quantity);
    }

    /** @test */
    public function adjust_creates_inventory_movement_record(): void
    {
        $this->service->adjust($this->level, +5, 'Ajuste de prueba');

        $this->assertDatabaseHas('inventory_movements', [
            'variant_id' => $this->variant->id,
            'type'       => 'adjustment',
            'quantity'   => 5,
        ]);
    }

    /** @test */
    public function negative_adjust_decreases_available_quantity(): void
    {
        $before = $this->level->available_quantity;
        $this->service->adjust($this->level, -5, 'Ajuste negativo test');

        $this->level->refresh();
        $this->assertEquals($before - 5, $this->level->available_quantity);
    }

    // ─── Reserva ──────────────────────────────────────────────────────────────

    /** @test */
    public function reserve_increments_reserved_quantity(): void
    {
        $before = $this->level->reserved_quantity; // 0

        $this->service->reserve(
            $this->variant->id,
            $this->location->id,
            3,
            'subscription',
            1
        );

        $this->level->refresh();
        $this->assertEquals($before + 3, $this->level->reserved_quantity);
    }

    /** @test */
    public function reserve_fails_when_stock_insufficient(): void
    {
        $this->expectException(\RuntimeException::class);

        // Intentar reservar más de lo disponible (available=20)
        $this->service->reserve(
            $this->variant->id,
            $this->location->id,
            999,
            'subscription',
            1
        );
    }

    /** @test */
    public function reserve_returns_inventory_reservation_model(): void
    {
        $reservation = $this->service->reserve(
            $this->variant->id,
            $this->location->id,
            2,
            'subscription',
            1
        );

        $this->assertInstanceOf(\App\Models\InventoryReservation::class, $reservation);
        $this->assertEquals(2, $reservation->quantity);
        $this->assertEquals('active', $reservation->status);
    }

    // ─── Liberación de reserva ────────────────────────────────────────────────

    /** @test */
    public function release_decrements_reserved_quantity(): void
    {
        $reservation = $this->service->reserve(
            $this->variant->id,
            $this->location->id,
            4,
            'subscription',
            2
        );

        $this->level->refresh();
        $afterReserve = $this->level->reserved_quantity;

        $this->service->release($reservation);

        $this->level->refresh();
        $this->assertEquals($afterReserve - 4, $this->level->reserved_quantity);
    }

    /** @test */
    public function release_marks_reservation_as_released(): void
    {
        $reservation = $this->service->reserve(
            $this->variant->id,
            $this->location->id,
            2,
            'subscription',
            3
        );

        $this->service->release($reservation);

        $reservation->refresh();
        $this->assertEquals('released', $reservation->status);
    }

    // ─── Summary ──────────────────────────────────────────────────────────────

    /** @test */
    public function summary_returns_in_stock_low_stock_and_out_of_stock_keys(): void
    {
        $summary = $this->service->summary();

        $this->assertArrayHasKey('in_stock', $summary);
        $this->assertArrayHasKey('low_stock', $summary);
        $this->assertArrayHasKey('out_of_stock', $summary);
    }

    /** @test */
    public function summary_counts_are_integers(): void
    {
        $summary = $this->service->summary();

        $this->assertIsInt($summary['in_stock']);
        $this->assertIsInt($summary['low_stock']);
        $this->assertIsInt($summary['out_of_stock']);
    }
}

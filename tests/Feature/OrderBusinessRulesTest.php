<?php

namespace Tests\Feature;

use App\Models\MockSubscription;
use App\Models\MockOrder;
use App\Models\MockCustomer;
use App\Services\OrderStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de reglas de negocio críticas de pedidos.
 *
 * Usa la API real de OrderStateMachine:
 *   canTransition(MockOrder, string): bool
 *   transition(MockOrder, string, ?User, ?string): void
 *
 * Cubre:
 * - Pago rechazado NUNCA llega a transmitted ni ready_to_transmit.
 * - Idempotencia: shopify_order_id único por tabla.
 * - Pedido cancelado no puede reiniciarse.
 * - Pedido entregado es terminal sin transiciones.
 * - Flujo feliz completo draft → delivered.
 */
class OrderBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    private OrderStateMachine $machine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->machine = app(OrderStateMachine::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function orderWithStatus(string $status): MockOrder
    {
        $customer     = MockCustomer::factory()->create();
        $subscription = MockSubscription::factory()->create(['customer_id' => $customer->id]);
        $payment      = \App\Models\MockPayment::factory()->create(['mock_subscription_id' => $subscription->id]);

        return MockOrder::factory()->create([
            'mock_subscription_id' => $subscription->id,
            'mock_payment_id'      => $payment->id,
            'internal_status'      => $status,
        ]);
    }

    // ─── Pago rechazado ───────────────────────────────────────────────────────

    /** @test */
    public function payment_rejected_order_cannot_be_transmitted(): void
    {
        $order = $this->orderWithStatus('payment_rejected');

        $canTransmit = $this->machine->canTransition($order, 'transmitted');
        $canReadyToTransmit = $this->machine->canTransition($order, 'ready_to_transmit');

        $this->assertFalse($canTransmit, 'payment_rejected NUNCA puede ir a transmitted');
        $this->assertFalse($canReadyToTransmit, 'payment_rejected NUNCA puede ir a ready_to_transmit');
    }

    /** @test */
    public function payment_rejected_attempting_transition_throws_exception(): void
    {
        $order = $this->orderWithStatus('payment_rejected');

        $this->expectException(\InvalidArgumentException::class);

        $this->machine->transition($order, 'ready_to_transmit', null, 'Intento inválido');
    }

    /** @test */
    public function payment_rejected_does_not_change_status_on_failed_transition(): void
    {
        $order = $this->orderWithStatus('payment_rejected');

        try {
            $this->machine->transition($order, 'transmitted');
        } catch (\InvalidArgumentException $e) {
            // expected
        }

        $order->refresh();
        $this->assertEquals('payment_rejected', $order->internal_status,
            'El estado no debe cambiar después de una transición fallida');
    }

    // ─── Idempotencia de pedidos (shopify_order_id único) ─────────────────────

    /** @test */
    public function same_shopify_order_id_cannot_be_inserted_twice(): void
    {
        $customer = MockCustomer::factory()->create();
        $sub      = MockSubscription::factory()->create(['customer_id' => $customer->id]);
        $payment1 = \App\Models\MockPayment::factory()->create(['mock_subscription_id' => $sub->id]);

        MockOrder::factory()->create([
            'mock_subscription_id' => $sub->id,
            'mock_payment_id'      => $payment1->id,
            'shopify_order_id'     => 'idempotency_test_001',
        ]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        $payment2 = \App\Models\MockPayment::factory()->create(['mock_subscription_id' => $sub->id]);
        MockOrder::factory()->create([
            'mock_subscription_id' => $sub->id,
            'mock_payment_id'      => $payment2->id,
            'shopify_order_id'     => 'idempotency_test_001',
        ]);
    }

    // ─── Pedido cancelado es terminal ─────────────────────────────────────────

    /** @test */
    public function cancelled_order_cannot_be_set_to_ready_to_transmit(): void
    {
        $order = $this->orderWithStatus('cancelled');
        $this->assertFalse($this->machine->canTransition($order, 'ready_to_transmit'));
    }

    /** @test */
    public function cancelled_order_cannot_be_set_to_payment_approved(): void
    {
        $order = $this->orderWithStatus('cancelled');
        $this->assertFalse($this->machine->canTransition($order, 'payment_approved'));
    }

    /** @test */
    public function cancelled_order_has_no_allowed_transitions(): void
    {
        $allowed = $this->machine->allowedFrom('cancelled');
        $this->assertEmpty($allowed, 'Un pedido cancelado no tiene transiciones disponibles');
    }

    // ─── Pedido entregado es terminal ─────────────────────────────────────────

    /** @test */
    public function delivered_order_cannot_go_back_to_shipped(): void
    {
        $order = $this->orderWithStatus('delivered');
        $this->assertFalse($this->machine->canTransition($order, 'shipped'));
    }

    /** @test */
    public function delivered_order_cannot_be_cancelled(): void
    {
        $order = $this->orderWithStatus('delivered');
        $this->assertFalse($this->machine->canTransition($order, 'cancelled'));
    }

    /** @test */
    public function delivered_order_has_no_allowed_transitions(): void
    {
        $allowed = $this->machine->allowedFrom('delivered');
        $this->assertEmpty($allowed, 'Un pedido entregado no tiene transiciones disponibles');
    }

    // ─── transition registra historial ───────────────────────────────────────

    /** @test */
    public function valid_transition_records_history_and_updates_status(): void
    {
        $order = $this->orderWithStatus('payment_approved');

        $this->machine->transition($order, 'ready_to_transmit', null, 'Procesamiento automatizado');

        $order->refresh();
        $this->assertEquals('ready_to_transmit', $order->internal_status);

        $this->assertDatabaseHas('order_status_history', [
            'mock_order_id' => $order->id,
            'from_status'   => 'payment_approved',
            'to_status'     => 'ready_to_transmit',
        ]);
    }

    // ─── Flujo feliz completo ─────────────────────────────────────────────────

    /** @test */
    public function complete_happy_path_draft_to_delivered(): void
    {
        $customer = MockCustomer::factory()->create();
        $sub      = MockSubscription::factory()->create(['customer_id' => $customer->id]);
        $payment  = \App\Models\MockPayment::factory()->create(['mock_subscription_id' => $sub->id]);
        $order    = MockOrder::factory()->create([
            'mock_subscription_id' => $sub->id,
            'mock_payment_id'      => $payment->id,
            'internal_status'      => 'draft',
        ]);

        $steps = [
            'pending_payment',
            'payment_approved',
            'ready_to_transmit',
            'transmitting',
            'transmitted',
            'confirmed_by_shopify',
            'preparing',
            'ready_to_ship',
            'shipped',
            'delivered',
        ];

        foreach ($steps as $step) {
            $this->machine->transition($order, $step, null, "Auto test: → $step");
            $order->refresh();
            $this->assertEquals($step, $order->internal_status,
                "El pedido debería estar en '$step' después de la transición");
        }

        // Verificar que se crearon 10 registros de historial
        $historyCount = \DB::table('order_status_history')
            ->where('mock_order_id', $order->id)
            ->count();

        $this->assertEquals(10, $historyCount,
            'El flujo completo debe generar 10 registros en order_status_history');
    }
}

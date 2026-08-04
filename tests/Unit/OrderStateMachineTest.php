<?php

namespace Tests\Unit;

use App\Models\MockOrder;
use App\Services\OrderStateMachine;
use Tests\TestCase;

/**
 * Tests de la máquina de estados de pedidos.
 *
 * Este test usa MockOrder parcial (sin RefreshDatabase) para los tests
 * que solo verifican la lógica de estados, y RefreshDatabase + factory
 * para los tests que requieren persistencia.
 *
 * API real de OrderStateMachine:
 *   canTransition(MockOrder $order, string $to): bool
 *   transition(MockOrder $order, string $to, ?User, ?string): void
 *   allowedFrom(string $status): array
 */
class OrderStateMachineTest extends TestCase
{
    // NOTA: No usamos RefreshDatabase aquí para los tests de lógica pura.
    // Los tests que requieren DB están en OrderBusinessRulesTest (Feature).

    private OrderStateMachine $machine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->machine = new OrderStateMachine();
    }

    /**
     * Crea un MockOrder en memoria (sin guardar en DB) con el estado dado.
     */
    private function mockOrderWithStatus(string $status): MockOrder
    {
        $order = new MockOrder();
        $order->internal_status = $status;
        return $order;
    }

    // ─── Transiciones válidas ──────────────────────────────────────────────────

    /** @test */
    public function payment_approved_can_transition_to_ready_to_transmit(): void
    {
        $order = $this->mockOrderWithStatus('payment_approved');
        $this->assertTrue($this->machine->canTransition($order, 'ready_to_transmit'));
    }

    /** @test */
    public function draft_can_transition_to_pending_payment(): void
    {
        $order = $this->mockOrderWithStatus('draft');
        $this->assertTrue($this->machine->canTransition($order, 'pending_payment'));
    }

    /** @test */
    public function shipped_can_transition_to_delivered(): void
    {
        $order = $this->mockOrderWithStatus('shipped');
        $this->assertTrue($this->machine->canTransition($order, 'delivered'));
    }

    /** @test */
    public function sync_error_can_retry_toward_ready_to_transmit(): void
    {
        $order = $this->mockOrderWithStatus('sync_error');
        $this->assertTrue($this->machine->canTransition($order, 'ready_to_transmit'),
            'sync_error debe poder reintentar hacia ready_to_transmit');
    }

    // ─── Reglas críticas — payment_rejected nunca transmite ───────────────────

    /** @test */
    public function payment_rejected_cannot_go_to_transmitted(): void
    {
        $order = $this->mockOrderWithStatus('payment_rejected');
        $this->assertFalse($this->machine->canTransition($order, 'transmitted'),
            'payment_rejected NUNCA puede ir a transmitted');
    }

    /** @test */
    public function payment_rejected_cannot_go_to_ready_to_transmit(): void
    {
        $order = $this->mockOrderWithStatus('payment_rejected');
        $this->assertFalse($this->machine->canTransition($order, 'ready_to_transmit'),
            'payment_rejected NUNCA puede ir a ready_to_transmit');
    }

    /** @test */
    public function payment_rejected_only_allows_cancel(): void
    {
        $allowed = $this->machine->allowedFrom('payment_rejected');
        $this->assertEquals(['cancelled'], $allowed,
            'payment_rejected solo puede cancelarse');
    }

    // ─── Terminal: delivered ──────────────────────────────────────────────────

    /** @test */
    public function delivered_cannot_transition_to_anything(): void
    {
        $order = $this->mockOrderWithStatus('delivered');
        $this->assertFalse($this->machine->canTransition($order, 'cancelled'));
        $this->assertFalse($this->machine->canTransition($order, 'shipped'));
    }

    /** @test */
    public function delivered_allowedFrom_is_empty(): void
    {
        $allowed = $this->machine->allowedFrom('delivered');
        $this->assertEmpty($allowed, 'delivered es estado terminal: sin transiciones');
    }

    // ─── Terminal: cancelled ──────────────────────────────────────────────────

    /** @test */
    public function cancelled_cannot_transition_to_anything(): void
    {
        $order = $this->mockOrderWithStatus('cancelled');
        $this->assertFalse($this->machine->canTransition($order, 'payment_approved'));
        $this->assertFalse($this->machine->canTransition($order, 'transmitted'));
        $this->assertFalse($this->machine->canTransition($order, 'ready_to_transmit'));
    }

    /** @test */
    public function cancelled_allowedFrom_is_empty(): void
    {
        $allowed = $this->machine->allowedFrom('cancelled');
        $this->assertEmpty($allowed, 'cancelled es estado terminal: sin transiciones');
    }

    // ─── allowedFrom devuelve array para todos los estados ───────────────────

    /** @test */
    public function allowedFrom_returns_array_for_every_known_state(): void
    {
        $states = [
            'draft', 'pending_payment', 'payment_approved', 'payment_rejected',
            'ready_to_transmit', 'transmitting', 'sync_error', 'transmitted',
            'confirmed_by_shopify', 'preparing', 'ready_to_ship',
            'shipped', 'returned', 'delivered', 'cancelled',
        ];

        foreach ($states as $state) {
            $this->assertIsArray(
                $this->machine->allowedFrom($state),
                "allowedFrom('$state') debe devolver array"
            );
        }
    }

    // ─── Flujo feliz — canTransition paso a paso ──────────────────────────────

    /** @test */
    public function happy_path_every_step_can_transition(): void
    {
        $transitions = [
            'draft'                 => 'pending_payment',
            'pending_payment'       => 'payment_approved',
            'payment_approved'      => 'ready_to_transmit',
            'ready_to_transmit'     => 'transmitting',
            'transmitting'          => 'transmitted',
            'transmitted'           => 'confirmed_by_shopify',
            'confirmed_by_shopify'  => 'preparing',
            'preparing'             => 'ready_to_ship',
            'ready_to_ship'         => 'shipped',
            'shipped'               => 'delivered',
        ];

        foreach ($transitions as $from => $to) {
            $order = $this->mockOrderWithStatus($from);
            $this->assertTrue(
                $this->machine->canTransition($order, $to),
                "Transición '$from' → '$to' debe ser válida en el flujo feliz"
            );
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\MockCustomer;
use App\Models\MockSubscription;
use App\Models\MockOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de integración del panel administrativo.
 *
 * Cubre:
 * - El panel requiere autenticación.
 * - Tamara (decision_owner) puede acceder a clientes, pedidos, suscripciones.
 * - Un usuario sin rol no puede acceder al panel.
 * - Las rutas de listado responden 200.
 * - Las transiciones de estado de pedido via HTTP funcionan.
 */
class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $tamara;
    private User $noRoleUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $this->tamara = User::where('email', 'tamara@promarine.com.ar')
            ->firstOrFail();

        $this->noRoleUser = User::factory()->create([
            'email' => 'noRole@test.com',
        ]);
    }

    // ─── Autenticación ────────────────────────────────────────────────────────

    /** @test */
    public function unauthenticated_user_is_redirected_from_admin(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    /** @test */
    public function unauthenticated_user_cannot_access_customers(): void
    {
        $this->get('/admin/customers')->assertRedirect('/login');
    }

    // ─── Acceso de Tamara ─────────────────────────────────────────────────────

    /** @test */
    public function tamara_can_access_admin_dashboard(): void
    {
        $this->actingAs($this->tamara)
             ->get('/admin/dashboard')
             ->assertStatus(200);
    }

    /** @test */
    public function tamara_can_access_customers_list(): void
    {
        $this->actingAs($this->tamara)
             ->get('/admin/customers')
             ->assertStatus(200);
    }

    /** @test */
    public function tamara_can_access_orders_list(): void
    {
        $this->actingAs($this->tamara)
             ->get('/admin/orders')
             ->assertStatus(200);
    }

    /** @test */
    public function tamara_can_access_subscriptions_list(): void
    {
        $this->actingAs($this->tamara)
             ->get('/admin/subscriptions')
             ->assertStatus(200);
    }

    /** @test */
    public function tamara_can_access_inventory(): void
    {
        $this->actingAs($this->tamara)
             ->get('/admin/inventory')
             ->assertStatus(200);
    }

    /** @test */
    public function tamara_can_access_cart_matrix(): void
    {
        $this->actingAs($this->tamara)
             ->get('/admin/cart-matrix')
             ->assertStatus(200);
    }

    /** @test */
    public function tamara_can_access_shopify_sync(): void
    {
        $this->actingAs($this->tamara)
             ->get('/admin/integrations/shopify')
             ->assertStatus(200);
    }

    // ─── Tamara no puede gestionar usuarios ───────────────────────────────────

    /** @test */
    public function tamara_cannot_access_user_management(): void
    {
        // decision_owner no tiene manage_users
        $response = $this->actingAs($this->tamara)
                         ->get('/admin/users');

        // Debe ser 403 o redirigir, no 200
        $this->assertNotEquals(200, $response->getStatusCode(),
            'Tamara no debe poder ver la gestión de usuarios (solo super_admin)');
    }

    // ─── Usuario sin rol ──────────────────────────────────────────────────────

    /** @test */
    public function user_without_role_cannot_access_admin(): void
    {
        $response = $this->actingAs($this->noRoleUser)
                         ->get('/admin/dashboard');

        $this->assertNotEquals(200, $response->getStatusCode(),
            'Un usuario sin rol no debe acceder al panel admin');
    }

    // ─── Transición de estado via HTTP ────────────────────────────────────────

    /** @test */
    public function tamara_can_transition_order_status(): void
    {
        $customer     = MockCustomer::factory()->create();
        $subscription = MockSubscription::factory()->create(['customer_id' => $customer->id]);
        $payment      = \App\Models\MockPayment::factory()->create(['mock_subscription_id' => $subscription->id]);
        $order        = MockOrder::factory()->create([
            'mock_subscription_id' => $subscription->id,
            'mock_payment_id'      => $payment->id,
            'internal_status'      => 'payment_approved',
        ]);

        $response = $this->actingAs($this->tamara)
                         ->post("/admin/orders/{$order->id}/transition", [
                             'to'     => 'ready_to_transmit',
                             'reason' => 'Test automático',
                         ]);

        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals('ready_to_transmit', $order->internal_status);
    }

    /** @test */
    public function invalid_order_transition_returns_error(): void
    {
        $customer     = MockCustomer::factory()->create();
        $subscription = MockSubscription::factory()->create(['customer_id' => $customer->id]);
        $payment      = \App\Models\MockPayment::factory()->create(['mock_subscription_id' => $subscription->id]);
        $order        = MockOrder::factory()->create([
            'mock_subscription_id' => $subscription->id,
            'mock_payment_id'      => $payment->id,
            'internal_status'      => 'delivered',
        ]);

        // Intentar cancelar un pedido entregado
        $response = $this->actingAs($this->tamara)
                         ->post("/admin/orders/{$order->id}/transition", [
                             'to'     => 'cancelled',
                             'reason' => 'Test de transición inválida',
                         ]);

        // Debe redirigir con error, no 500
        $response->assertRedirect();

        $order->refresh();
        // El estado no cambió
        $this->assertEquals('delivered', $order->internal_status);
    }
}

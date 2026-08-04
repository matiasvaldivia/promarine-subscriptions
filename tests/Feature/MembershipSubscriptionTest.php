<?php

namespace Tests\Feature;

use App\Mail\MembershipRequested;
use App\Models\MembershipSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MembershipSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    public function test_page_explains_benefits_without_a_product_purchase(): void
    {
        $this->get('/Plan-de-subscription')->assertOk()
            ->assertSee('MEMBRESÍA ANUAL')->assertSee('Precios para miembros')
            ->assertSee('Entregas prioritarias')->assertSee('Información exclusiva')
            ->assertSee('Ningún producto agregado')->assertSee('no genera cobros');
    }

    public function test_request_does_not_create_product_subscription_order_or_payment(): void
    {
        Mail::fake();
        $response = $this->post('/Plan-de-subscription', [
            'name' => 'Miembro Demo', 'email' => 'miembro@example.com',
            'phone' => '2615550000', 'community_updates' => '1', 'consent_terms' => '1',
        ]);
        $membership = MembershipSubscription::firstOrFail();
        $response->assertRedirect(route('membership.confirmation', ['membership' => $membership->uuid, 'email' => 'sent']));
        $this->assertSame('annual', $membership->billing_period);
        $this->assertSame('pending_confirmation', $membership->status);
        $this->assertTrue($membership->community_updates);
        $this->assertDatabaseCount('mock_subscriptions', 0);
        $this->assertDatabaseCount('mock_orders', 0);
        $this->assertDatabaseCount('mock_payments', 0);
        Mail::assertSent(MembershipRequested::class, fn ($mail) => $mail->hasTo('miembro@example.com'));
    }

    public function test_consent_is_required_and_pending_email_is_idempotent(): void
    {
        $this->post('/Plan-de-subscription', ['name' => 'Sin permiso', 'email' => 'no@example.com'])->assertSessionHasErrors('consent_terms');
        Mail::fake();
        $payload = ['name' => 'Persona', 'email' => 'PERSONA@example.com', 'consent_terms' => '1'];
        $this->post('/Plan-de-subscription', $payload);
        $this->post('/Plan-de-subscription', $payload);
        $this->assertDatabaseCount('membership_subscriptions', 1);
    }
}

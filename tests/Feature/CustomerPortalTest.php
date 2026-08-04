<?php

namespace Tests\Feature;

use App\Mail\CustomerPortalAccessCode;
use App\Models\MockCustomer;
use App\Models\MockPayment;
use App\Models\MockSubscription;
use App\Models\SubscriptionPlan;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    public function test_customer_receives_one_time_code_and_sees_plan_calendar(): void
    {
        Mail::fake();
        $this->seed(ProductSeeder::class);
        $plan = SubscriptionPlan::with('variant.product')->firstOrFail();
        $customer = MockCustomer::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Cliente Promarine',
            'email' => 'cliente@example.com',
            'phone' => '1122334455',
            'province' => 'Buenos Aires',
            'locality' => 'La Plata',
            'postal_code' => '1900',
            'address' => 'Calle Demo',
            'address_number' => '123',
            'is_mock' => true,
            'environment' => 'local',
        ]);
        $subscription = MockSubscription::create([
            'uuid' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'subscription_plan_id' => $plan->id,
            'provider' => 'mercadopago',
            'provider_subscription_id' => 'mock_subscription_portal',
            'status' => 'payment_approved',
            'amount' => $plan->amount,
            'currency' => 'ARS',
            'frequency' => 30,
            'frequency_type' => 'days',
            'next_billing_at' => now()->addDays(30),
            'started_at' => now(),
            'is_mock' => true,
            'environment' => 'local',
            'metadata_json' => ['community_preferences' => ['podcasts' => true, 'talks' => true]],
        ]);
        MockPayment::create([
            'mock_subscription_id' => $subscription->id,
            'provider_payment_id' => 'mock_payment_portal',
            'status' => 'approved',
            'amount' => $plan->amount,
            'currency' => 'ARS',
            'idempotency_key' => 'portal-test',
            'is_mock' => true,
            'environment' => 'local',
        ]);

        $this->post(route('customer.portal.send-code'), ['email' => 'CLIENTE@example.com'])
            ->assertRedirect(route('customer.portal.verify'))
            ->assertSessionHas('customer_portal_pending_email', 'cliente@example.com');

        $sentCode = null;
        Mail::assertSent(CustomerPortalAccessCode::class, function (CustomerPortalAccessCode $mail) use (&$sentCode) {
            $sentCode = $mail->code;
            return $mail->hasTo('cliente@example.com');
        });
        $this->assertMatchesRegularExpression('/^\d{6}$/', $sentCode);
        $this->assertDatabaseHas('customer_portal_codes', ['email' => 'cliente@example.com', 'attempts' => 0]);

        $this->post(route('customer.portal.verify-code'), ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertDatabaseHas('customer_portal_codes', ['email' => 'cliente@example.com', 'attempts' => 1]);

        $this->post(route('customer.portal.verify-code'), ['code' => $sentCode])
            ->assertRedirect(route('customer.portal.dashboard'))
            ->assertSessionHas('customer_portal_email', 'cliente@example.com');

        $this->get(route('customer.portal.dashboard'))
            ->assertOk()
            ->assertSee($plan->variant->product->name)
            ->assertSee('Calendario de pagos y entregas')
            ->assertSee('Próximo pago programado')
            ->assertSee('Podcasts Promarine')
            ->assertSee('Notificaciones activadas')
            ->assertSee('Comprar otro producto');

        $this->get('/?recomprar=1')
            ->assertOk()
            ->assertSee('Confirmá dónde lo recibís')
            ->assertSee('DATOS GUARDADOS')
            ->assertSee('cliente@example.com')
            ->assertSee('subscriptionWizard', false);

        $this->post(route('checkout.simulate'), [
            'plan_id' => $plan->id,
            'name' => 'Nombre modificado',
            'email' => 'otro@example.com',
            'phone' => '0000000000',
            'province' => 'Córdoba',
            'locality' => 'Otra localidad',
            'postal_code' => '5000',
            'address' => 'Otra calle',
            'address_number' => '999',
            'people' => 1,
            'doses_per_day' => 1,
            'delivery_frequency' => 30,
            'use_saved_customer' => 1,
            'consent_recurring' => 1,
            'consent_terms' => 1,
            'consent_order' => 1,
            'consent_policy' => 1,
        ])->assertRedirect();

        $this->assertDatabaseCount('mock_customers', 1);
        $repurchase = MockSubscription::latest('id')->firstOrFail();
        $this->assertSame($customer->id, $repurchase->customer_id);
        $this->assertSame('verified_portal', data_get($repurchase->metadata_json, 'customer_source'));
        $this->assertDatabaseHas('mock_customers', [
            'id' => $customer->id,
            'email' => 'cliente@example.com',
            'address' => 'Calle Demo',
            'address_number' => '123',
        ]);

        $this->post(route('customer.portal.logout'))
            ->assertRedirect(route('customer.portal.request'))
            ->assertSessionMissing('customer_portal_email');
    }

    public function test_unknown_email_receives_neutral_response_without_sending_code(): void
    {
        Mail::fake();
        $this->app->detectEnvironment(fn () => 'production');

        $this->post(route('customer.portal.send-code'), ['email' => 'desconocido@example.com'])
            ->assertRedirect(route('customer.portal.verify'))
            ->assertSessionHas('status');

        Mail::assertNothingSent();
        $this->assertDatabaseCount('customer_portal_codes', 0);
    }

    public function test_local_demo_explains_when_email_has_no_active_plan(): void
    {
        Mail::fake();
        $this->app->detectEnvironment(fn () => 'local');

        $this->from(route('customer.portal.request'))
            ->post(route('customer.portal.send-code'), ['email' => 'desconocido@example.com'])
            ->assertRedirect(route('customer.portal.request'))
            ->assertSessionHasErrors('email')
            ->assertSessionMissing('customer_portal_pending_email');

        Mail::assertNothingSent();
        $this->assertDatabaseCount('customer_portal_codes', 0);
    }

    public function test_portal_dashboard_requires_a_verified_email_session(): void
    {
        $this->get(route('customer.portal.dashboard'))
            ->assertRedirect(route('customer.portal.request'));
    }
}

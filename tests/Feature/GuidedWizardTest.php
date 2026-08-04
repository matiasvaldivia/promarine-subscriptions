<?php

namespace Tests\Feature;

use App\Mail\MockPurchaseConfirmed;
use App\Models\MockSubscription;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GuidedWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guided_wizard_uses_database_backed_products_presentations_and_plans(): void
    {
        $this->seed(ProductSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSee('Armá tu plan paso a paso')
            ->assertSee('Tu calendario de pagos y entregas')
            ->assertSee('Comunidad Promarine')
            ->assertSee('Pendiente de validación')
            ->assertSee('Rastrear mi pedido')
            ->assertSee('Política de cambios y devoluciones')
            ->assertSee('https://www.instagram.com/promarineantioxidants/', false)
            ->assertSee('e-Trade ID Verified Company');

        $this->assertFileExists(public_path('assets/promarine/etrade-verified.png'));
        $landingHtml = $this->get('/')->getContent();
        $this->assertSame(7, substr_count($landingHtml, 'class="pm-consent-info"'));
        $this->assertStringContainsString('El botón informativo no activa la opción automáticamente.', $landingHtml);
        $this->assertStringContainsString('En esta demostración no se ejecutan débitos reales.', file_get_contents(resource_path('js/app.js')));
        $this->assertStringContainsString('x-ref="calendar"', $landingHtml);
        $this->assertStringContainsString('aria-label="Ver próximo ciclo"', $landingHtml);
        $this->assertStringContainsString('startCalendarDrag(event)', file_get_contents(resource_path('js/app.js')));

        $this->assertDatabaseCount('products', 4);
        $this->assertDatabaseCount('product_variants', 8);
        $this->assertDatabaseCount('subscription_plans', 24);
        $this->assertDatabaseHas('product_variants', ['name' => 'Botella', 'image_path' => '/assets/promarine/demo/marine-epic-bottle.png']);
        $this->assertDatabaseHas('product_variants', ['name' => 'Monodosis', 'image_path' => '/assets/promarine/demo/marine-epic-box.png']);
        $this->assertDatabaseHas('product_variants', ['name' => 'Botella', 'image_path' => '/assets/promarine/demo/marine-fusion-bottle.png']);
        $this->assertDatabaseHas('product_variants', ['name' => 'Monodosis', 'image_path' => '/assets/promarine/demo/marine-fusion-box.png']);
        $this->assertDatabaseHas('product_variants', ['name' => 'Botella', 'image_path' => '/assets/promarine/demo/echa-marine-bottle.png']);
        $this->assertDatabaseHas('product_variants', ['name' => 'Monodosis', 'image_path' => '/assets/promarine/demo/echa-marine-box.png']);
        $this->assertDatabaseHas('product_variants', ['name' => 'Botella', 'image_path' => '/assets/promarine/demo/marine-pulse-bottle.png']);
        $this->assertDatabaseHas('product_variants', ['name' => 'Monodosis', 'image_path' => '/assets/promarine/demo/marine-pulse-box.png']);
        $this->assertDatabaseHas('subscription_plans', ['name' => 'Suscripción flexible', 'discount_value' => 10]);
        $this->assertDatabaseHas('subscription_plans', ['name' => 'Plan 3 meses', 'discount_value' => 12]);
        $this->assertDatabaseHas('subscription_plans', ['name' => 'Plan 6 meses', 'discount_value' => 15]);
        $this->assertSame(8, DB::table('product_variants')->whereNull('units_per_package')->count());
        $this->assertSame(8, DB::table('product_variants')->whereNull('recommended_daily_dose')->count());
    }

    public function test_checkout_persists_optional_community_preferences(): void
    {
        $this->seed(ProductSeeder::class);
        Mail::fake();

        $planId = DB::table('subscription_plans')->value('id');

        $response = $this->withSession(['_token' => 'guided-wizard-test'])->post(route('checkout.simulate'), [
            '_token' => 'guided-wizard-test',
            'plan_id' => $planId,
            'name' => 'Persona Demo',
            'email' => 'demo@example.test',
            'phone' => '2804000000',
            'province' => 'Chubut',
            'locality' => 'Puerto Madryn',
            'postal_code' => '9120',
            'address' => 'Avenida Demo',
            'address_number' => '123',
            'people' => 1,
            'doses_per_day' => 1,
            'delivery_frequency' => 30,
            'community_member' => '1',
            'notify_podcasts' => '1',
            'consent_recurring' => '1',
            'consent_terms' => '1',
            'consent_order' => '1',
            'consent_policy' => '1',
        ]);

        $subscription = MockSubscription::firstOrFail();
        $response->assertRedirect(route('checkout.payment', $subscription));
        $this->get(route('checkout.payment', $subscription))
            ->assertOk()
            ->assertSee('¿Cómo querés pagar?')
            ->assertSee('Pago de demostración');

        $this->withSession(['_token' => 'guided-payment-test'])
            ->post(route('checkout.process', $subscription), [
                '_token' => 'guided-payment-test',
                'mock_result' => 'approved',
            ])
            ->assertOk()
            ->assertSee('¡Tu Promarine está en camino!')
            ->assertSee('Email enviado');

        Mail::assertSent(MockPurchaseConfirmed::class, fn ($mail) => $mail->hasTo('demo@example.test'));

        $preferences = $subscription->metadata_json['community_preferences'];

        $this->assertTrue($preferences['member']);
        $this->assertTrue($preferences['podcasts']);
        $this->assertFalse($preferences['talks']);
    }
}

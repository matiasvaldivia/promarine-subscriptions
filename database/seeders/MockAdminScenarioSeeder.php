<?php

namespace Database\Seeders;

use App\Models\Fulfillment;
use App\Models\MockCustomer;
use App\Models\MockIgsEvent;
use App\Models\MockOrder;
use App\Models\MockPayment;
use App\Models\MockSubscription;
use App\Models\OrderStatusHistory;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MockAdminScenarioSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedCustomers();
            $this->seedScenarios();
        });
    }

    private function seedCustomers(): void
    {
        $customers = [
            ['Valentina Torres',  'valentina.torres@ejemplo.com',  '011-4523-1234', 'Buenos Aires', 'active'],
            ['Rodrigo Mendez',    'rodrigo.mendez@ejemplo.com',    '011-4212-5678', 'Córdoba',      'active'],
            ['Carolina Vega',     'carolina.vega@ejemplo.com',     '011-4890-9012', 'Buenos Aires', 'active'],
            ['Matías Sánchez',    'matias.sanchez@ejemplo.com',    '011-4567-3456', 'Santa Fe',     'inactive'],
            ['Lucía Ramírez',     'lucia.ramirez@ejemplo.com',     '011-4234-7890', 'Mendoza',      'active'],
            ['Nicolás Flores',    'nicolas.flores@ejemplo.com',    '011-4901-2345', 'Buenos Aires', 'blocked'],
            ['Sofía Gutiérrez',   'sofia.gutierrez@ejemplo.com',   '011-4678-6789', 'Tucumán',      'active'],
            ['Alejandro Ruiz',    'alejandro.ruiz@ejemplo.com',    '011-4345-0123', 'Buenos Aires', 'active'],
            ['Camila López',      'camila.lopez@ejemplo.com',      '011-4912-4567', 'Córdoba',      'active'],
            ['Facundo Herrera',   'facundo.herrera@ejemplo.com',   '011-4789-8901', 'Buenos Aires', 'active'],
        ];

        foreach ($customers as [$name, $email, $phone, $province, $status]) {
            MockCustomer::updateOrCreate(
                ['email' => $email],
                [
                    'uuid'         => (string) Str::uuid(),
                    'name'         => $name,
                    'phone'        => $phone,
                    'province'     => $province,
                    'locality'     => $province === 'Buenos Aires' ? 'CABA' : $province,
                    'postal_code'  => rand(1000, 9999),
                    'address'      => 'Calle Falsa',
                    'address_number'=> rand(100, 9999),
                    'status'       => $status,
                    'source'       => 'wizard',
                    'is_mock'      => true,
                    'environment'  => 'local',
                ]
            );
        }

        $this->command->info('✓ 10 clientes mock creados');
    }

    private function seedScenarios(): void
    {
        $plans = SubscriptionPlan::all();
        if ($plans->isEmpty()) {
            $this->command->warn('⚠ Sin planes. Ejecutá ProductSeeder primero.');
            return;
        }

        $customers = MockCustomer::limit(10)->get();
        $statuses  = [
            ['payment_approved', 'active'],
            ['payment_approved', 'active'],
            ['payment_approved', 'active'],
            ['paused',           'paused'],
            ['payment_rejected', 'error'],
            ['payment_approved', 'active'],
            ['cancelled',        'cancelled'],
            ['payment_approved', 'active'],
            ['payment_approved', 'active'],
            ['paused',           'paused'],
        ];

        // 20 suscripciones con escenarios variados
        foreach ($customers as $i => $customer) {
            [$subStatus] = $statuses[$i % count($statuses)];

            $plan = $plans->random();

            // Idempotencia: evitar duplicar provider_subscription_id
            if (MockSubscription::where('customer_id', $customer->id)->exists()) continue;

            $subscription = MockSubscription::create([
                'uuid'                     => (string) Str::uuid(),
                'customer_id'              => $customer->id,
                'subscription_plan_id'     => $plan->id,
                'provider'                 => 'mercadopago',
                'provider_subscription_id' => 'mock_pre_admin_' . bin2hex(random_bytes(6)),
                'status'                   => $subStatus,
                'amount'                   => $plan->amount,
                'currency'                 => 'ARS',
                'frequency'                => 30,
                'frequency_type'           => 'days',
                'current_cycle'            => rand(1, 6),
                'next_billing_at'          => now()->addDays(rand(3, 29)),
                'started_at'               => now()->subDays(rand(30, 180)),
                'paused_at'                => $subStatus === 'paused' ? now()->subDays(5) : null,
                'cancelled_at'             => $subStatus === 'cancelled' ? now()->subDays(10) : null,
                'influencer_code'          => $i === 0 ? 'PROMO2024' : null,
                'is_mock'                  => true,
                'environment'              => 'local',
                'metadata_json'            => ['source' => 'admin_seeder'],
            ]);

            // 30 pedidos con estados variados
            $this->seedOrders($subscription, $plan->amount, $i);
        }

        $this->command->info('✓ Suscripciones y pedidos mock creados');
    }

    private function seedOrders(MockSubscription $subscription, float $amount, int $idx): void
    {
        $orderScenarios = [
            ['internal_status' => 'delivered',           'financial' => 'paid',    'fulfillment' => 'fulfilled',   'withFulfillment' => true,  'withIgs' => true],
            ['internal_status' => 'transmitted',          'financial' => 'paid',    'fulfillment' => 'unfulfilled', 'withFulfillment' => false, 'withIgs' => false],
            ['internal_status' => 'sync_error',           'financial' => 'paid',    'fulfillment' => 'unfulfilled', 'withFulfillment' => false, 'withIgs' => false],
            ['internal_status' => 'payment_approved',     'financial' => 'paid',    'fulfillment' => 'unfulfilled', 'withFulfillment' => false, 'withIgs' => false],
            ['internal_status' => 'cancelled',            'financial' => 'voided',  'fulfillment' => 'unfulfilled', 'withFulfillment' => false, 'withIgs' => false],
        ];

        $scenario = $orderScenarios[$idx % count($orderScenarios)];

        // Pago
        $payment = MockPayment::create([
            'mock_subscription_id' => $subscription->id,
            'provider_payment_id'  => 'mock_pmt_admin_' . bin2hex(random_bytes(6)),
            'status'               => $scenario['internal_status'] === 'payment_approved' ? 'pending' : 'approved',
            'amount'               => $amount,
            'currency'             => 'ARS',
            'billing_cycle'        => 1,
            'idempotency_key'      => 'admin-seed-' . bin2hex(random_bytes(8)),
            'approved_at'          => now()->subDays(rand(1, 30)),
            'is_mock'              => true,
            'environment'          => 'local',
        ]);

        // Pedido
        $order = MockOrder::create([
            'mock_subscription_id' => $subscription->id,
            'mock_payment_id'      => $payment->id,
            'shopify_order_id'     => 'shopify_' . bin2hex(random_bytes(6)),
            'status'               => 'created',
            'internal_status'      => $scenario['internal_status'],
            'financial_status'     => $scenario['financial'],
            'fulfillment_status'   => $scenario['fulfillment'],
            'total'                => $amount,
            'transmitted_at'       => in_array($scenario['internal_status'], ['transmitted', 'delivered', 'sync_error']) ? now()->subDays(7) : null,
            'confirmed_at'         => $scenario['internal_status'] === 'delivered' ? now()->subDays(6) : null,
            'dispatched_at'        => $scenario['internal_status'] === 'delivered' ? now()->subDays(5) : null,
            'delivered_at'         => $scenario['internal_status'] === 'delivered' ? now()->subDays(2) : null,
            'cancelled_at'         => $scenario['internal_status'] === 'cancelled' ? now()->subDays(1) : null,
            'is_mock'              => true,
            'environment'          => 'local',
        ]);

        // Status history
        OrderStatusHistory::create([
            'mock_order_id' => $order->id,
            'from_status'   => null,
            'to_status'     => 'payment_approved',
            'reason'        => 'Pago aprobado por MercadoPago',
        ]);

        if ($scenario['internal_status'] !== 'payment_approved') {
            OrderStatusHistory::create([
                'mock_order_id' => $order->id,
                'from_status'   => 'payment_approved',
                'to_status'     => $scenario['internal_status'],
                'reason'        => 'Actualizado por admin_seeder',
            ]);
        }

        // Fulfillment para pedidos entregados
        if ($scenario['withFulfillment']) {
            $tracking = 'AR' . rand(1000000000, 9999999999) . 'AR';
            Fulfillment::create([
                'mock_order_id'           => $order->id,
                'external_fulfillment_id' => 'mock_ff_' . bin2hex(random_bytes(6)),
                'status'                  => 'delivered',
                'carrier'                 => ['OCA', 'Andreani', 'Correo Argentino'][rand(0, 2)],
                'tracking_number'         => $tracking,
                'tracking_url'            => "https://tracking.oca.com.ar/{$tracking}",
                'prepared_at'             => now()->subDays(6),
                'shipped_at'              => now()->subDays(5),
                'delivered_at'            => now()->subDays(2),
                'is_mock'                 => true,
                'environment'             => 'local',
            ]);
        }

        // Evento IGS
        if ($scenario['withIgs']) {
            $commission = round($amount * 0.10, 2);
            MockIgsEvent::create([
                'mock_order_id'  => $order->id,
                'event_id'       => 'igs_admin_' . bin2hex(random_bytes(6)),
                'type'           => 'igs.sale.created',
                'status'         => 'sent',
                'commission'     => $commission,
                'base_amount'    => $amount,
                'influencer_code'=> $subscription->influencer_code,
                'is_mock'        => true,
                'environment'    => 'local',
                'payload_json'   => ['sanitized' => true],
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Mail\MockPurchaseConfirmed;
use App\Models\MockCustomer;
use App\Models\MockSubscription;
use App\Models\SubscriptionPlan;
use App\Services\MercadoPagoGatewayInterface;
use App\Services\MockSubscriptionFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class CheckoutController extends Controller
{
    public function store(Request $request, MercadoPagoGatewayInterface $mercadoPago, MockSubscriptionFlow $flow)
    {
        $data = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'name' => 'required|string|max:100',
            'email' => 'required|email',
            'phone' => 'required|string|max:30',
            'province' => 'required|string|max:80',
            'locality' => 'required|string|max:100',
            'postal_code' => 'required|string|max:12',
            'address' => 'required|string|max:150',
            'address_number' => 'required|string|max:20',
            'apartment' => 'nullable|string|max:40',
            'address_reference' => 'nullable|string|max:180',
            'people' => 'required|integer|min:1|max:10',
            'doses_per_day' => 'required|numeric|min:0.25|max:10',
            'delivery_frequency' => 'required|integer|in:15,30,45,60',
            'influencer_code' => 'nullable|string|max:50',
            'consent_recurring' => 'accepted',
            'consent_terms' => 'accepted',
            'consent_order' => 'accepted',
            'consent_policy' => 'accepted',
            'community_member' => 'sometimes|boolean',
            'notify_podcasts' => 'sometimes|boolean',
            'notify_talks' => 'sometimes|boolean',
            'use_saved_customer' => 'sometimes|boolean',
        ]);

        $plan = SubscriptionPlan::with('variant.product')->findOrFail($data['plan_id']);
        $portalEmail = $request->session()->get('customer_portal_email');
        $portalCustomer = null;
        if ($portalEmail) {
            $portalCustomerIds = MockCustomer::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($portalEmail)])
                ->pluck('id');
            $activeCustomerId = MockSubscription::query()
                ->whereIn('customer_id', $portalCustomerIds)
                ->whereNotIn('status', ['payment_rejected', 'cancelled'])
                ->latest('id')
                ->value('customer_id');
            $portalCustomer = MockCustomer::find($activeCustomerId)
                ?? MockCustomer::query()->whereIn('id', $portalCustomerIds)->latest('id')->first();
        }

        if ($portalCustomer) {
            $data['email'] = $portalCustomer->email;

            if (! empty($data['use_saved_customer'])) {
                foreach (['name', 'phone', 'province', 'locality', 'postal_code', 'address', 'address_number', 'apartment', 'address_reference'] as $field) {
                    $data[$field] = $portalCustomer->{$field};
                }
            }
        }

        $subscription = DB::transaction(function () use ($data, $plan, $mercadoPago, $portalCustomer) {
            if ($portalCustomer) {
                if (empty($data['use_saved_customer'])) {
                    $portalCustomer->update([
                        'name' => $data['name'],
                        'phone' => $data['phone'],
                        'province' => $data['province'],
                        'locality' => $data['locality'],
                        'postal_code' => $data['postal_code'],
                        'address' => $data['address'],
                        'address_number' => $data['address_number'],
                        'apartment' => $data['apartment'] ?? null,
                        'address_reference' => $data['address_reference'] ?? null,
                    ]);
                }

                $customerId = $portalCustomer->id;
            } else {
                $customerId = DB::table('mock_customers')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'province' => $data['province'],
                    'locality' => $data['locality'],
                    'postal_code' => $data['postal_code'],
                    'address' => $data['address'],
                    'address_number' => $data['address_number'],
                    'apartment' => $data['apartment'] ?? null,
                    'address_reference' => $data['address_reference'] ?? null,
                    'is_mock' => true,
                    'environment' => 'local',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $gateway = $mercadoPago->createSubscription(['amount' => $plan->amount]);

            return MockSubscription::create([
                'uuid' => (string) Str::uuid(),
                'customer_id' => $customerId,
                'subscription_plan_id' => $plan->id,
                'provider' => 'mercadopago',
                'provider_subscription_id' => $gateway->id,
                'status' => 'authorized',
                'amount' => $plan->amount,
                'currency' => $plan->currency,
                'frequency' => $data['delivery_frequency'],
                'frequency_type' => 'days',
                'next_billing_at' => now()->addDays($data['delivery_frequency']),
                'started_at' => now(),
                'influencer_code' => $data['influencer_code'] ?? null,
                'is_mock' => true,
                'environment' => 'local',
                'metadata_json' => [
                    'source' => 'guided_landing',
                    'customer_source' => $portalCustomer ? 'verified_portal' : 'new_checkout',
                    'product' => $plan->variant->product->name,
                    'presentation' => $plan->variant->name,
                    'people' => $data['people'],
                    'doses_per_day' => $data['doses_per_day'],
                    'delivery_frequency' => $data['delivery_frequency'],
                    'shipping_status' => 'pending_configuration',
                    'consents' => ['recurring', 'terms', 'order_after_payment', 'cancellation_policy'],
                    'community_preferences' => [
                        'member' => ! empty($data['community_member']),
                        'podcasts' => ! empty($data['notify_podcasts']),
                        'talks' => ! empty($data['notify_talks']),
                    ],
                ],
            ]);
        });

        return redirect()->route('checkout.payment', $subscription);
    }

    public function payment(MockSubscription $subscription)
    {
        abort_unless($subscription->is_mock, 404);

        $subscription->load('plan.variant.product');
        $customer = DB::table('mock_customers')->where('id', $subscription->customer_id)->firstOrFail();

        return view('checkout.payment', compact('subscription', 'customer'));
    }

    public function process(Request $request, MockSubscription $subscription, MockSubscriptionFlow $flow)
    {
        abort_unless($subscription->is_mock, 404);
        $request->validate(['mock_result' => 'required|in:approved']);

        $processed = $flow->processPayment($subscription, 'approved', 'checkout-'.$subscription->uuid);
        $subscription = $subscription->fresh()->load('plan.variant.product');
        $customer = DB::table('mock_customers')->where('id', $subscription->customer_id)->firstOrFail();
        $mailSent = false;

        if (! $processed['duplicate']) {
            try {
                Mail::to($customer->email)->send(new MockPurchaseConfirmed(
                    $subscription,
                    $customer,
                    $processed['payment'],
                    $processed['order'],
                ));
                $mailSent = true;
            } catch (Throwable $exception) {
                Log::warning('No se pudo enviar la confirmación de compra simulada.', [
                    'subscription_uuid' => $subscription->uuid,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return view('checkout.confirmation', [
            'subscription' => $subscription,
            'customer' => $customer,
            'payment' => $processed['payment'],
            'order' => $processed['order'],
            'igsId' => $processed['igs_id'] ?? null,
            'mailSent' => $mailSent,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\MockCustomer;
use App\Models\MockSubscription;
use App\Models\Policy;
use App\Models\Product;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function __invoke(Request $request)
    {
        $customerProfile = null;
        $portalEmail = $request->session()->get('customer_portal_email');

        if ($portalEmail) {
            $customerIds = MockCustomer::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($portalEmail)])
                ->pluck('id');
            $subscription = MockSubscription::query()
                ->with('customer')
                ->whereIn('customer_id', $customerIds)
                ->whereNotIn('status', ['payment_rejected', 'cancelled'])
                ->latest('id')
                ->first();
            $customer = $subscription?->customer ?? MockCustomer::query()->whereIn('id', $customerIds)->latest('id')->first();

            if ($customer) {
                $customerProfile = [
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'province' => $customer->province,
                    'locality' => $customer->locality,
                    'postal_code' => $customer->postal_code,
                    'address' => $customer->address,
                    'address_number' => $customer->address_number,
                    'apartment' => $customer->apartment,
                    'address_reference' => $customer->address_reference,
                    'community' => data_get($subscription?->metadata_json, 'community_preferences', []),
                ];
            }
        }

        return view('landing.index', [
            'products' => Product::with('variants.plans')->where('enabled', true)->orderByDesc('featured')->get(),
            'policies' => Policy::with('currentVersion')->get(),
            'customerProfile' => $customerProfile,
            'portalRepurchase' => $request->boolean('recomprar') && $customerProfile !== null,
        ]);
    }
}

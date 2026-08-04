<?php

namespace App\Http\Controllers;

use App\Mail\CustomerPortalAccessCode;
use App\Models\CustomerPortalCode;
use App\Models\MockCustomer;
use App\Models\MockSubscription;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class CustomerPortalController extends Controller
{
    private const CODE_EXPIRY_MINUTES = 10;
    private const DEMO_EMAIL = 'tamara.demo@invalid.local';
    private const DEMO_CODE = '246810';

    public function requestForm(Request $request)
    {
        if ($request->session()->has('customer_portal_email')) {
            return redirect()->route('customer.portal.dashboard');
        }

        return view('customer-portal.request');
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email:rfc|max:190']);
        $email = mb_strtolower(trim($data['email']));
        $customer = MockCustomer::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereHas('subscriptions', fn ($query) => $query->whereNotIn('status', ['payment_rejected', 'cancelled']))
            ->latest('id')
            ->first();

        $request->session()->put('customer_portal_pending_email', $email);

        if (! $customer && app()->environment('local')) {
            $request->session()->forget('customer_portal_pending_email');
            Log::info('Intento de acceso al portal sin un plan activo.', [
                'email_hash' => hash('sha256', $email),
            ]);

            return back()
                ->withInput()
                ->withErrors(['email' => 'No encontramos una compra simulada activa con ese correo. Completá primero el pago de demostración usando el mismo email.']);
        }

        if ($customer) {
            CustomerPortalCode::query()
                ->where('email', $email)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            $isDemoCustomer = app()->environment('local') && $email === self::DEMO_EMAIL;
            $code = $isDemoCustomer ? self::DEMO_CODE : (string) random_int(100000, 999999);
            if ($isDemoCustomer) {
                $request->session()->put('customer_portal_demo_code', $code);
            } else {
                $request->session()->forget('customer_portal_demo_code');
            }
            $portalCode = CustomerPortalCode::create([
                'email' => $email,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(self::CODE_EXPIRY_MINUTES),
                'request_ip' => $request->ip(),
            ]);

            try {
                Mail::to($email)->send(new CustomerPortalAccessCode($code, self::CODE_EXPIRY_MINUTES));
            } catch (Throwable $exception) {
                $portalCode->update(['consumed_at' => now()]);
                Log::warning('No se pudo enviar el código del portal del cliente.', [
                    'email_hash' => hash('sha256', $email),
                    'exception' => $exception->getMessage(),
                ]);

                if (app()->environment('local')) {
                    $request->session()->forget('customer_portal_pending_email');

                    return back()
                        ->withInput()
                        ->withErrors(['email' => 'No pudimos enviar el código en este momento. Revisá la configuración de correo o volvé a intentarlo.']);
                }
            }
        }

        return redirect()->route('customer.portal.verify')
            ->with('status', $customer
                ? 'Código enviado. Revisá tu bandeja de entrada y también correo no deseado.'
                : 'Si el correo está asociado a un plan, vas a recibir un código de acceso.');
    }

    public function verifyForm(Request $request)
    {
        $email = $request->session()->get('customer_portal_pending_email');
        if (! $email) {
            return redirect()->route('customer.portal.request');
        }

        return view('customer-portal.verify', [
            'maskedEmail' => $this->maskEmail($email),
            'demoCode' => app()->environment('local') && $email === self::DEMO_EMAIL
                ? $request->session()->get('customer_portal_demo_code')
                : null,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.digits' => 'Ingresá los seis números del código.',
        ]);

        $email = $request->session()->get('customer_portal_pending_email');
        if (! $email) {
            return redirect()->route('customer.portal.request');
        }

        $access = CustomerPortalCode::query()
            ->where('email', $email)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $access || $access->expires_at->isPast() || $access->attempts >= 5) {
            throw ValidationException::withMessages([
                'code' => 'El código venció. Solicitá uno nuevo para continuar.',
            ]);
        }

        if (! Hash::check($data['code'], $access->code_hash)) {
            $access->increment('attempts');
            throw ValidationException::withMessages([
                'code' => 'El código no coincide. Revisalo e intentá nuevamente.',
            ]);
        }

        $access->update(['consumed_at' => now()]);
        $request->session()->regenerate();
        $request->session()->put('customer_portal_email', $email);
        $request->session()->forget('customer_portal_pending_email');
        $request->session()->forget('customer_portal_demo_code');

        return redirect()->route('customer.portal.dashboard');
    }

    public function dashboard(Request $request)
    {
        $email = $request->session()->get('customer_portal_email');
        if (! $email) {
            return redirect()->route('customer.portal.request');
        }

        $customerIds = MockCustomer::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->pluck('id');

        $subscription = MockSubscription::query()
            ->with(['plan.variant.product', 'payments', 'orders'])
            ->whereIn('customer_id', $customerIds)
            ->whereNotIn('status', ['payment_rejected', 'cancelled'])
            ->latest('id')
            ->first();

        if (! $subscription) {
            $request->session()->forget('customer_portal_email');
            return redirect()->route('customer.portal.request')
                ->withErrors(['email' => 'No encontramos un plan asociado a ese correo.']);
        }

        $customer = MockCustomer::findOrFail($subscription->customer_id);

        return view('customer-portal.dashboard', [
            'customer' => $customer,
            'subscription' => $subscription,
            'schedule' => $this->buildSchedule($subscription),
            'community' => data_get($subscription->metadata_json, 'community_preferences', []),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['customer_portal_email', 'customer_portal_pending_email']);
        $request->session()->regenerateToken();

        return redirect()->route('customer.portal.request');
    }

    private function buildSchedule(MockSubscription $subscription): array
    {
        $frequency = max(1, (int) $subscription->frequency);
        $firstBilling = CarbonImmutable::parse($subscription->started_at ?? $subscription->created_at)->startOfDay();
        $nextBilling = $subscription->next_billing_at
            ? CarbonImmutable::parse($subscription->next_billing_at)->startOfDay()
            : $firstBilling->addDays($frequency);
        $hasApprovedPayment = $subscription->payments->contains('status', 'approved');

        return collect(range(0, 5))->map(function (int $index) use ($frequency, $firstBilling, $nextBilling, $hasApprovedPayment) {
            $billing = $index === 0 ? $firstBilling : $nextBilling->addDays(($index - 1) * $frequency);
            $deliveryStart = $billing->addDays(3);
            $deliveryEnd = $billing->addDays(7);

            return [
                'cycle' => $index + 1,
                'billing' => $billing,
                'billing_label' => $billing->locale('es')->translatedFormat('D d M'),
                'month' => mb_strtoupper($billing->locale('es')->translatedFormat('M')),
                'day' => $billing->format('d'),
                'delivery_label' => $deliveryStart->locale('es')->translatedFormat('d M').' al '.$deliveryEnd->locale('es')->translatedFormat('d M'),
                'is_paid' => $index === 0 && $hasApprovedPayment,
                'is_next' => $index === 1,
            ];
        })->all();
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($name, 0, min(2, mb_strlen($name)));

        return $visible.str_repeat('•', max(3, mb_strlen($name) - mb_strlen($visible))).'@'.$domain;
    }
}

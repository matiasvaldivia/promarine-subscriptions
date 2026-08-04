<?php

namespace App\Http\Controllers;

use App\Models\MockSubscription;
use App\Services\MockSubscriptionFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Recibe notificaciones IPN/webhook de MercadoPago.
 *
 * MP envía POST a /webhooks/mercadopago con:
 *   - type: "subscription_preapproval" | "payment" | etc.
 *   - data.id: ID del recurso en MP
 *
 * También valida la firma X-Signature con MP_CLAVE_WEBHOOK.
 */
class WebhookMercadoPagoController extends Controller
{
    public function handle(Request $request, MockSubscriptionFlow $flow)
    {
        $rawBody = $request->getContent();

        // ── Validar firma si está configurada ───────────────────────────────
        $secret = config('services.mercadopago.webhook_secret');
        if ($secret) {
            $xSignature  = $request->header('X-Signature', '');
            $xRequestId  = $request->header('X-Request-Id', '');
            $dataId      = $request->input('data.id', $request->query('data.id', ''));

            // MP firma con: ts + '.' + v1
            // Cadena a firmar: "id:{dataId};request-id:{xRequestId};ts:{ts};"
            $ts  = '';
            $v1  = '';
            foreach (explode(',', $xSignature) as $part) {
                [$key, $val] = array_pad(explode('=', trim($part), 2), 2, '');
                if ($key === 'ts') $ts = $val;
                if ($key === 'v1') $v1 = $val;
            }

            $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
            $expected = hash_hmac('sha256', $manifest, $secret);

            if (! $v1 || ! hash_equals($expected, $v1)) {
                Log::warning('Webhook MP: firma inválida o ausente', [
                    'expected' => $expected,
                    'received' => $v1,
                    'manifest' => $manifest,
                ]);
                return response()->json(['error' => 'invalid_signature'], 400);
            }
        }

        // ── Parsear payload ─────────────────────────────────────────────────
        $type   = $request->input('type', $request->query('topic', ''));
        $dataId = $request->input('data.id', $request->query('id', ''));

        Log::info('Webhook MercadoPago recibido', [
            'type'    => $type,
            'data_id' => $dataId,
            'payload' => $request->all(),
        ]);

        // ── Procesar según tipo ─────────────────────────────────────────────
        match (true) {
            str_contains($type, 'subscription_preapproval') => $this->handlePreapproval($dataId, $flow),
            str_contains($type, 'payment')                  => $this->handlePayment($dataId, $flow),
            default                                          => null,
        };

        // MP espera HTTP 200 inmediatamente
        return response()->json(['received' => true]);
    }

    private function handlePreapproval(string $mpId, MockSubscriptionFlow $flow): void
    {
        if (! $mpId) return;

        $subscription = MockSubscription::where('provider_subscription_id', $mpId)->first();

        if (! $subscription) {
            Log::warning('Webhook MP: preapproval no encontrado en DB', ['mp_id' => $mpId]);
            return;
        }

        // En sandbox, cuando el pago se aprueba el preapproval pasa a "authorized"
        // Procesamos el pago completo: activa suscripción + registra pago + envía email
        if (in_array($subscription->status, ['pending', 'payment_rejected'])) {
            try {
                $subscription->load('plan.variant.product');
                $customer = \Illuminate\Support\Facades\DB::table('mock_customers')
                    ->where('id', $subscription->customer_id)
                    ->first();

                $processed = $flow->processPayment($subscription, 'approved', 'webhook-mp-' . $mpId);

                if (! $processed['duplicate'] && $customer) {
                    \Illuminate\Support\Facades\Mail::to($customer->email)
                        ->send(new \App\Mail\MockPurchaseConfirmed(
                            $subscription->fresh()->load('plan.variant.product'),
                            $customer,
                            $processed['payment'],
                            $processed['order'],
                        ));
                }

                Log::info('Webhook MP: suscripción activada por IPN', [
                    'uuid'      => $subscription->uuid,
                    'mp_id'     => $mpId,
                    'duplicate' => $processed['duplicate'],
                ]);
            } catch (\Throwable $e) {
                Log::error('Webhook MP: error procesando preapproval', [
                    'mp_id'   => $mpId,
                    'error'   => $e->getMessage(),
                ]);
            }
        } else {
            Log::info('Webhook MP: preapproval ya procesado, ignorando', [
                'uuid'   => $subscription->uuid,
                'status' => $subscription->status,
            ]);
        }
    }

    private function handlePayment(string $mpPaymentId, MockSubscriptionFlow $flow): void
    {
        if (! $mpPaymentId) return;

        Log::info('Webhook MP: pago recibido', ['payment_id' => $mpPaymentId]);

        // En una implementación completa, aquí buscaríamos el pago en MP
        // y actualizaríamos la suscripción correspondiente.
        // Por ahora solo logueamos.
    }
}

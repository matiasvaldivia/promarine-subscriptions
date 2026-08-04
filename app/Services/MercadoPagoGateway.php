<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use MercadoPago\Client\PreApproval\PreApprovalClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use Throwable;

/**
 * Implementacion real del gateway de Mercado Pago.
 *
 * Mapea los 5 metodos de la interfaz a las llamadas del SDK dx-php v3:
 *   - createSubscription: preapproval create (suscripcion recurrente)
 *   - getSubscription:    preapproval get
 *   - pauseSubscription:  preapproval update (status=paused)
 *   - cancelSubscription: preapproval update (status=cancelled)
 *   - getPayment:         preapproval get (incluye info de pagos)
 *
 * Requiere las env vars:
 *   - MP_ACCESS_TOKEN
 *   - MP_PUBLIC_KEY
 *
 * Se activa en lugar del mock cuando MP_ACCESS_TOKEN esta presente.
 * El service provider (App\Providers\MercadoPagoServiceProvider) hace
 * la decision de cual implementacion inyectar.
 */
class MercadoPagoGateway implements MercadoPagoGatewayInterface
{
    private PreApprovalClient $preapproval;

    public function __construct()
    {
        // Configurar SDK una sola vez por request
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL); // sandbox

        $this->preapproval = new PreApprovalClient();
    }

    public function createSubscription(array $data): GatewayResult
    {
        try {
            $amount = $data['amount'] ?? 0;
            $frequency = $data['delivery_frequency'] ?? 30;
            // MP usa meses para frecuencia recurrente. Mapeamos:
            // 15 days -> 1 mes, 30 days -> 1 mes, 45 days -> 1 mes, 60 days -> 2 meses
            $frequencyMonths = max(1, (int) round($frequency / 30));

            $preapproval = $this->preapproval->create([
                'reason' => $data['reason'] ?? 'Promarine Suscripcion',
                'external_reference' => $data['external_reference'] ?? null,
                'payer_email' => $data['payer_email'] ?? null,
                'back_url' => $data['back_url'] ?? route('checkout.payment', ['subscription' => $data['external_reference'] ?? 'pending'], false),
                'auto_recurring' => [
                    'frequency' => $frequencyMonths,
                    'frequency_type' => 'months',
                    'transaction_amount' => (float) $amount,
                    'currency_id' => $data['currency'] ?? 'ARS',
                ],
                'status' => 'pending',
                'notification_url' => config('services.mercadopago.webhook_url'),
            ]);

            return new GatewayResult(
                success: true,
                id: $preapproval->id,
                status: $preapproval->status ?? 'pending',
                payload: [
                    'init_point' => $preapproval->init_point ?? null,
                    'is_mock' => false,
                    'environment' => 'sandbox',
                    'amount' => $amount,
                    'frequency_months' => $frequencyMonths,
                ]
            );
        } catch (MPApiException $e) {
            Log::error('MercadoPago createSubscription API error', [
                'status' => $e->getApiResponse()->getStatusCode(),
                'message' => $e->getMessage(),
            ]);

            return new GatewayResult(
                success: false,
                id: '',
                status: 'error',
                payload: [
                    'error' => $e->getMessage(),
                    'http_status' => $e->getApiResponse()->getStatusCode(),
                ]
            );
        } catch (Throwable $e) {
            Log::error('MercadoPago createSubscription exception', [
                'message' => $e->getMessage(),
            ]);

            return new GatewayResult(
                success: false,
                id: '',
                status: 'error',
                payload: ['error' => $e->getMessage()]
            );
        }
    }

    public function getSubscription(string $id): GatewayResult
    {
        try {
            $preapproval = $this->preapproval->get($id);

            return new GatewayResult(
                success: true,
                id: $preapproval->id,
                status: $preapproval->status ?? 'unknown',
                payload: [
                    'status' => $preapproval->status,
                    'init_point' => $preapproval->init_point ?? null,
                    'payer_email' => $preapproval->payer_email ?? null,
                    'external_reference' => $preapproval->external_reference ?? null,
                    'is_mock' => false,
                ]
            );
        } catch (Throwable $e) {
            Log::error('MercadoPago getSubscription error', ['id' => $id, 'message' => $e->getMessage()]);
            return new GatewayResult(false, $id, 'error', ['error' => $e->getMessage()]);
        }
    }

    public function pauseSubscription(string $id): GatewayResult
    {
        return $this->updateSubscriptionStatus($id, 'paused');
    }

    public function cancelSubscription(string $id): GatewayResult
    {
        return $this->updateSubscriptionStatus($id, 'cancelled');
    }

    private function updateSubscriptionStatus(string $id, string $status): GatewayResult
    {
        try {
            $preapproval = $this->preapproval->update($id, ['status' => $status]);

            return new GatewayResult(
                success: true,
                id: $preapproval->id,
                status: $preapproval->status ?? $status,
                payload: ['status' => $preapproval->status, 'is_mock' => false]
            );
        } catch (Throwable $e) {
            Log::error("MercadoPago update status={$status} error", ['id' => $id, 'message' => $e->getMessage()]);
            return new GatewayResult(false, $id, 'error', ['error' => $e->getMessage()]);
        }
    }

    public function getPayment(string $id): GatewayResult
    {
        // Para preapproval, getPayment es equivalente a getSubscription
        // porque MP incluye info de pagos en el preapproval.
        // Si se necesita un payment especifico, usar PaymentClient.
        return $this->getSubscription($id);
    }
}

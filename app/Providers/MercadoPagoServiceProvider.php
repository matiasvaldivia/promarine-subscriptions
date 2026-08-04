<?php

namespace App\Providers;

use App\Services\MercadoPagoGateway;
use App\Services\MercadoPagoGatewayInterface;
use App\Services\MockMercadoPagoGateway;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider que decide que implementacion de MercadoPagoGateway
 * inyectar segun el entorno:
 *   - Si MP_ACCESS_TOKEN esta presente y no vacio -> MercadoPagoGateway (real)
 *   - En cualquier otro caso                       -> MockMercadoPagoGateway
 *
 * El mock sigue funcionando para desarrollo local sin credenciales.
 * Para test de sandbox o produccion, exportar MP_ACCESS_TOKEN.
 */
class MercadoPagoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MercadoPagoGatewayInterface::class, function ($app) {
            $accessToken = config('services.mercadopago.access_token');

            if (! empty($accessToken)) {
                return new MercadoPagoGateway();
            }

            return new MockMercadoPagoGateway();
        });
    }

    public function boot(): void
    {
        //
    }
}

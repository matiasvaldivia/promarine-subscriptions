<?php

namespace App\Providers;

use App\Services\{IGSGatewayInterface,MercadoPagoGatewayInterface,MockIGSGateway,MockMercadoPagoGateway,MockShopifyGateway,ShopifyGatewayInterface};

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MercadoPagoGatewayInterface::class, MockMercadoPagoGateway::class);
        $this->app->bind(ShopifyGatewayInterface::class, MockShopifyGateway::class);
        $this->app->bind(IGSGatewayInterface::class, MockIGSGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

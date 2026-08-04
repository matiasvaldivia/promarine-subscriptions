<?php

namespace App\Providers;

use App\Services\{
    IGSGatewayInterface,
    MercadoPagoGatewayInterface,
    MockIGSGateway,
    MockMercadoPagoGateway,
    MockShopifyGateway,
    ShopifyGatewayInterface,
    CartMatrixService,
    OrderStateMachine,
    OrderService,
    SubscriptionService,
    InventoryService,
    ShopifySyncService,
    AuditService,
};

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ── Gateways ────────────────────────────────────────────────
        $this->app->bind(MercadoPagoGatewayInterface::class, MockMercadoPagoGateway::class);
        $this->app->bind(ShopifyGatewayInterface::class, MockShopifyGateway::class);
        $this->app->bind(IGSGatewayInterface::class, MockIGSGateway::class);

        // ── Admin services (singletons para performance) ──────────
        $this->app->singleton(OrderStateMachine::class);
        $this->app->singleton(CartMatrixService::class);
        $this->app->singleton(InventoryService::class);
        $this->app->singleton(AuditService::class);

        $this->app->bind(OrderService::class);
        $this->app->bind(SubscriptionService::class);
        $this->app->bind(ShopifySyncService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

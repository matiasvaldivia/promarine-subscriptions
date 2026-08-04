<?php

use App\Http\Controllers\{AdminController,AuthController,CheckoutController,CustomerPortalController,InterviewController,LandingController,MembershipController,SimulatorController,WebhookMercadoPagoController};
use Illuminate\Support\Facades\Route;

// ── Webhook MercadoPago — SIN CSRF (validación por firma HMAC interna) ──────
Route::post('/webhooks/mercadopago', [WebhookMercadoPagoController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhooks.mercadopago');

Route::get('/', LandingController::class)->name('landing');
Route::get('/plan-de-subscription', fn () => redirect()->route('membership.show', status: 301));
Route::get('/Plan-de-subscription', [MembershipController::class, 'show'])->name('membership.show');
Route::post('/Plan-de-subscription', [MembershipController::class, 'store'])->middleware('throttle:5,1')->name('membership.store');
Route::get('/Plan-de-subscription/{membership:uuid}/confirmacion', [MembershipController::class, 'confirmation'])->name('membership.confirmation');
Route::post('/checkout/simulate',[CheckoutController::class,'store'])->middleware('throttle:10,1')->name('checkout.simulate');
Route::get('/checkout/simulate/{subscription:uuid}/payment',[CheckoutController::class,'payment'])->name('checkout.payment');
Route::post('/checkout/simulate/{subscription:uuid}/process',[CheckoutController::class,'process'])->middleware('throttle:10,1')->name('checkout.process');
Route::prefix('mi-plan')->name('customer.portal.')->group(function(){Route::get('/',[CustomerPortalController::class,'requestForm'])->name('request');Route::post('/codigo',[CustomerPortalController::class,'sendCode'])->middleware('throttle:3,1')->name('send-code');Route::get('/verificar',[CustomerPortalController::class,'verifyForm'])->name('verify');Route::post('/verificar',[CustomerPortalController::class,'verify'])->middleware('throttle:8,1')->name('verify-code');Route::get('/calendario',[CustomerPortalController::class,'dashboard'])->name('dashboard');Route::post('/salir',[CustomerPortalController::class,'logout'])->name('logout');});
Route::middleware('guest')->group(function(){Route::get('/login',[AuthController::class,'show'])->name('login');Route::post('/login',[AuthController::class,'login'])->middleware('throttle:5,1')->name('login.submit');Route::get('/admin/login',fn()=>redirect()->route('login'));});
Route::middleware(['auth', 'role'])->prefix('admin')->group(function () {
    // ── Legacy admin (existentes — sin cambios) ────────────────────
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/interview', [InterviewController::class, 'index'])->name('admin.interview');
    Route::post('/interview/{question}', [InterviewController::class, 'save'])->name('admin.interview.save');
    Route::get('/interview/report', [InterviewController::class, 'report'])->name('admin.interview.report');
    Route::get('/simulator', [SimulatorController::class, 'index']);
    Route::post('/simulator', [SimulatorController::class, 'run']);

    // ── Dashboard ─────────────────────────────────────────────────
    Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin');
    Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');

    // ── Clientes ──────────────────────────────────────────────────
    Route::resource('customers', App\Http\Controllers\Admin\CustomerAdminController::class)
         ->names('admin.customers');

    // ── Usuarios (Solo Super Admin) ───────────────────────────────
    Route::middleware('role:super_admin')->group(function () {
        Route::resource('users', App\Http\Controllers\Admin\UserAdminController::class)
             ->names('admin.users');
        Route::post('users/{user}/toggle-status', [App\Http\Controllers\Admin\UserAdminController::class, 'toggleStatus'])
             ->name('admin.users.toggle-status');
    });

    // ── Productos ─────────────────────────────────────────────────
    Route::get('products', [App\Http\Controllers\Admin\ProductAdminController::class, 'index'])->name('admin.products.index');
    Route::get('products/{product}/edit', [App\Http\Controllers\Admin\ProductAdminController::class, 'edit'])->name('admin.products.edit');
    Route::put('products/{product}', [App\Http\Controllers\Admin\ProductAdminController::class, 'update'])->name('admin.products.update');

    // ── Matriz comercial (24 combinaciones) ───────────────────────
    Route::get('cart-matrix', [App\Http\Controllers\Admin\CartMatrixController::class, 'index'])->name('admin.cart-matrix');
    Route::put('cart-matrix/{row}', [App\Http\Controllers\Admin\CartMatrixController::class, 'update'])->name('admin.cart-matrix.update');

    // ── Inventario ────────────────────────────────────────────────
    Route::get('inventory', [App\Http\Controllers\Admin\InventoryController::class, 'index'])->name('admin.inventory');
    Route::post('inventory/{level}/sync', [App\Http\Controllers\Admin\InventoryController::class, 'sync'])->name('admin.inventory.sync');
    Route::post('inventory/{level}/adjust', [App\Http\Controllers\Admin\InventoryController::class, 'adjust'])->name('admin.inventory.adjust');

    // ── Pedidos ───────────────────────────────────────────────────
    Route::get('orders', [App\Http\Controllers\Admin\OrderAdminController::class, 'index'])->name('admin.orders.index');
    Route::get('orders/{order}', [App\Http\Controllers\Admin\OrderAdminController::class, 'show'])->name('admin.orders.show');
    Route::post('orders/{order}/transition', [App\Http\Controllers\Admin\OrderAdminController::class, 'transition'])->name('admin.orders.transition');

    // ── Fulfillments ──────────────────────────────────────────────
    Route::get('fulfillments', [App\Http\Controllers\Admin\FulfillmentAdminController::class, 'index'])->name('admin.fulfillments.index');
    Route::get('fulfillments/{fulfillment}', [App\Http\Controllers\Admin\FulfillmentAdminController::class, 'show'])->name('admin.fulfillments.show');

    // ── Suscripciones ─────────────────────────────────────────────
    Route::get('subscriptions', [App\Http\Controllers\Admin\SubscriptionAdminController::class, 'index'])->name('admin.subscriptions.index');
    Route::get('subscriptions/{subscription}', [App\Http\Controllers\Admin\SubscriptionAdminController::class, 'show'])->name('admin.subscriptions.show');
    Route::post('subscriptions/{subscription}/pause', [App\Http\Controllers\Admin\SubscriptionAdminController::class, 'pause'])->name('admin.subscriptions.pause');
    Route::post('subscriptions/{subscription}/resume', [App\Http\Controllers\Admin\SubscriptionAdminController::class, 'resume'])->name('admin.subscriptions.resume');
    Route::post('subscriptions/{subscription}/cancel', [App\Http\Controllers\Admin\SubscriptionAdminController::class, 'cancel'])->name('admin.subscriptions.cancel');

    // ── Pagos ─────────────────────────────────────────────────────
    Route::get('payments', [App\Http\Controllers\Admin\PaymentAdminController::class, 'index'])->name('admin.payments.index');
    Route::get('payments/{payment}', [App\Http\Controllers\Admin\PaymentAdminController::class, 'show'])->name('admin.payments.show');

    // ── Shopify sync ──────────────────────────────────────────────
    Route::get('integrations/shopify', [App\Http\Controllers\Admin\ShopifySyncController::class, 'index'])->name('admin.shopify.index');
    Route::post('integrations/shopify/run', [App\Http\Controllers\Admin\ShopifySyncController::class, 'run'])->name('admin.shopify.run');
    Route::get('integrations/shopify/{run}', [App\Http\Controllers\Admin\ShopifySyncController::class, 'show'])->name('admin.shopify.show');

    // ── Eventos de integración ────────────────────────────────────
    Route::get('integration-events', [App\Http\Controllers\Admin\IntegrationEventController::class, 'index'])->name('admin.integration-events');

    // ── IGS (comisiones) ──────────────────────────────────────────
    Route::get('igs', [App\Http\Controllers\Admin\IgsController::class, 'index'])->name('admin.igs.index');

    // ── Auditoría ─────────────────────────────────────────────────
    Route::get('audit-logs', [App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('admin.audit-logs');
});


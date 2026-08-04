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
Route::middleware('auth')->prefix('admin')->group(function(){Route::get('/',[AdminController::class,'index'])->name('admin');Route::post('/logout',[AuthController::class,'logout']);Route::get('/interview',[InterviewController::class,'index']);Route::post('/interview/{question}',[InterviewController::class,'save']);Route::get('/interview/report',[InterviewController::class,'report']);Route::get('/simulator',[SimulatorController::class,'index']);Route::post('/simulator',[SimulatorController::class,'run']);});

<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CLIENTES (mock_customers) — últimos 3 ===" . PHP_EOL;
$customers = DB::table('mock_customers')->latest('id')->take(3)->get();
foreach ($customers as $c) {
    echo PHP_EOL . "id={$c->id} uuid={$c->uuid}" . PHP_EOL;
    echo "  nombre:       {$c->name}" . PHP_EOL;
    echo "  email:        {$c->email}" . PHP_EOL;
    echo "  teléfono:     {$c->phone}" . PHP_EOL;
    echo "  provincia:    {$c->province}" . PHP_EOL;
    echo "  localidad:    {$c->locality}" . PHP_EOL;
    echo "  CP:           {$c->postal_code}" . PHP_EOL;
    echo "  dirección:    {$c->address} {$c->address_number}" . PHP_EOL;
    echo "  dpto:         " . ($c->apartment ?? '(vacío)') . PHP_EOL;
    echo "  referencia:   " . ($c->address_reference ?? '(vacío)') . PHP_EOL;
    echo "  creado:       {$c->created_at}" . PHP_EOL;
}

echo PHP_EOL . "=== SUSCRIPCIONES (mock_subscriptions) — últimas 3 ===" . PHP_EOL;
$subs = DB::table('mock_subscriptions')->latest('id')->take(3)->get();
foreach ($subs as $s) {
    $meta = json_decode($s->metadata_json, true);
    echo PHP_EOL . "id={$s->id} uuid={$s->uuid}" . PHP_EOL;
    echo "  status:       {$s->status}" . PHP_EOL;
    echo "  provider_id:  {$s->provider_subscription_id}" . PHP_EOL;
    echo "  amount:       {$s->amount} {$s->currency}" . PHP_EOL;
    echo "  frecuencia:   {$s->frequency} días" . PHP_EOL;
    echo "  producto:     " . ($meta['product'] ?? '?') . " · " . ($meta['presentation'] ?? '?') . PHP_EOL;
    echo "  personas:     " . ($meta['people'] ?? '?') . PHP_EOL;
    echo "  dosis/día:    " . ($meta['doses_per_day'] ?? '?') . PHP_EOL;
    echo "  mp_env:       " . ($meta['mp_environment'] ?? '?') . PHP_EOL;
    echo "  creado:       {$s->created_at}" . PHP_EOL;
}

echo PHP_EOL . "=== PAGOS (mock_payments) — últimos 3 ===" . PHP_EOL;
$payments = DB::table('mock_payments')->latest('id')->take(3)->get();
foreach ($payments as $p) {
    echo PHP_EOL . "id={$p->id} payment_id={$p->provider_payment_id}" . PHP_EOL;
    echo "  status:       {$p->status}" . PHP_EOL;
    echo "  amount:       {$p->amount} {$p->currency}" . PHP_EOL;
    echo "  creado:       {$p->created_at}" . PHP_EOL;
}

echo PHP_EOL . "=== ÓRDENES (mock_orders) — últimas 3 ===" . PHP_EOL;
$orders = DB::table('mock_orders')->latest('id')->take(3)->get();
if ($orders->isEmpty()) {
    echo "  (sin órdenes registradas aún)" . PHP_EOL;
} else {
    foreach ($orders as $o) {
        echo PHP_EOL . "id={$o->id} shopify_order_id={$o->shopify_order_id}" . PHP_EOL;
        echo "  status:       {$o->status}" . PHP_EOL;
        echo "  total:        {$o->total}" . PHP_EOL;
        echo "  creado:       {$o->created_at}" . PHP_EOL;
    }
}

echo PHP_EOL . "=== RESUMEN DE TABLAS ===" . PHP_EOL;
foreach (['mock_customers','mock_subscriptions','mock_payments','mock_orders','mock_igs_events','integration_events'] as $t) {
    $count = DB::table($t)->count();
    echo "  $t: $count registros" . PHP_EOL;
}

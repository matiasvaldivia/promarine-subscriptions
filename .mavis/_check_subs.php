<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MockSubscription;

echo "=== Estado de suscripciones en DB ===" . PHP_EOL;

$subs = MockSubscription::latest('id')->take(5)->get(['id', 'uuid', 'status', 'provider_subscription_id', 'created_at']);
foreach ($subs as $s) {
    echo "id={$s->id} uuid={$s->uuid} status={$s->status} mp_id={$s->provider_subscription_id} created={$s->created_at}" . PHP_EOL;
}

$pending = MockSubscription::where('status', 'pending')->latest()->first();
if ($pending) {
    echo PHP_EOL . "=== Suscripción PENDING encontrada ===" . PHP_EOL;
    echo "uuid:    " . $pending->uuid . PHP_EOL;
    echo "mp_id:   " . $pending->provider_subscription_id . PHP_EOL;
    echo "amount:  " . $pending->amount . PHP_EOL;
    echo PHP_EOL . "URL de retorno simulada (back_url desde MP):" . PHP_EOL;
    echo "http://localhost:8080/checkout/simulate/{$pending->uuid}/payment?collection_id=MPTEST123&collection_status=approved&payment_id=MPTEST123&status=approved&external_reference={$pending->uuid}" . PHP_EOL;
} else {
    echo PHP_EOL . "No hay suscripciones pending en DB." . PHP_EOL;
}

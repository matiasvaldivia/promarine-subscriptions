<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

// ── COLUMNAS de products ──────────────────────────────────
$prodCols = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name='products' ORDER BY ordinal_position");
$prodColNames = array_column($prodCols, 'column_name');

$products = DB::table('products')->get();
echo "=== PRODUCTOS ===" . PHP_EOL;
echo "Columnas: " . implode(' | ', $prodColNames) . PHP_EOL;
foreach ($products as $p) {
    $active = property_exists($p, 'is_active') ? ($p->is_active ? 'sí' : 'no') : 'n/a';
    echo "  [{$p->id}] {$p->name} | slug=" . ($p->slug ?? 'n/a') . " | activo={$active}" . PHP_EOL;
}

// ── COLUMNAS de product_variants ─────────────────────────
$varCols = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name='product_variants' ORDER BY ordinal_position");
$varColNames = array_column($varCols, 'column_name');

$variants = DB::table('product_variants')->get();
echo PHP_EOL . "=== VARIANTES ===" . PHP_EOL;
echo "Columnas: " . implode(' | ', $varColNames) . PHP_EOL;
foreach ($variants as $v) {
    $active = property_exists($v, 'is_active') ? ($v->is_active ? 'sí' : 'no') : 'n/a';
    $units  = property_exists($v, 'units_per_package') ? $v->units_per_package : 'n/a';
    $price  = property_exists($v, 'price') ? $v->price : 'n/a';
    echo "  [{$v->id}] prod_id={$v->product_id} | {$v->name} | sku=" . ($v->sku ?? 'n/a') . " | unidades={$units} | precio={$price} | activo={$active}" . PHP_EOL;
}

// ── COLUMNAS de subscription_plans ───────────────────────
$planCols = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name='subscription_plans' ORDER BY ordinal_position");
$planColNames = array_column($planCols, 'column_name');

$plans = DB::table('subscription_plans')->get();
echo PHP_EOL . "=== PLANES DE SUSCRIPCIÓN ===" . PHP_EOL;
echo "Columnas: " . implode(' | ', $planColNames) . PHP_EOL;
foreach ($plans as $pl) {
    $meta = json_decode($pl->metadata_json ?? '{}', true);
    $freqs = $meta['available_frequencies'] ?? null;
    $active = property_exists($pl, 'is_active') ? ($pl->is_active ? 'sí' : 'no') : 'n/a';
    echo "  [{$pl->id}] variant_id={$pl->variant_id} | {$pl->name} | \${$pl->amount} {$pl->currency}" . PHP_EOL;
    if ($freqs) echo "       frecuencias: " . implode(', ', $freqs) . " días" . PHP_EOL;
    if (property_exists($pl, 'min_doses')) echo "       dosis: {$pl->min_doses}-{$pl->max_doses}/día | personas: {$pl->min_people}-{$pl->max_people}" . PHP_EOL;
    if (property_exists($pl, 'minimum_cycles')) echo "       ciclos_mín: {$pl->minimum_cycles}" . PHP_EOL;
    echo "       activo={$active}" . PHP_EOL;
}

// ── WIZARD STEPS resumidos ────────────────────────────────
echo PHP_EOL . "=== WIZARD (7 PASOS) — CAMPOS CAPTURADOS ===" . PHP_EOL;
$steps = [
    1 => ['paso' => 'Producto',         'campos' => ['selectedProductId (product.id)']],
    2 => ['paso' => 'Presentación',     'campos' => ['selectedVariantId (variant.id)']],
    3 => ['paso' => 'Consumo',          'campos' => ['people (personas)', 'dosesPerDay (dosis/día)']],
    4 => ['paso' => 'Frecuencia',       'campos' => ['deliveryFrequency (15/30/45/60 días)']],
    5 => ['paso' => 'Plan',             'campos' => ['selectedPlanId (plan.id)']],
    6 => ['paso' => 'Dirección y envío','campos' => ['name', 'email', 'phone', 'province', 'locality', 'postal_code', 'address', 'address_number', 'apartment (opt)', 'address_reference (opt)', 'influencer_code (opt)']],
    7 => ['paso' => 'Consentimientos',  'campos' => ['consent_recurring', 'consent_terms', 'consent_order', 'consent_policy', 'community_member (opt)', 'notify_podcasts (opt)', 'notify_talks (opt)']],
];
foreach ($steps as $n => $s) {
    echo "  Paso {$n}: {$s['paso']}" . PHP_EOL;
    foreach ($s['campos'] as $c) echo "    · {$c}" . PHP_EOL;
}

// ── RESUMEN DB actual ─────────────────────────────────────
echo PHP_EOL . "=== REGISTROS EN DB ===" . PHP_EOL;
$tables = ['mock_customers','mock_subscriptions','mock_payments','mock_orders','mock_igs_events','integration_events'];
foreach ($tables as $t) {
    echo "  {$t}: " . DB::table($t)->count() . " registros" . PHP_EOL;
}

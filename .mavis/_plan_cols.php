<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

echo "=== SCHEMA subscription_plans ===" . PHP_EOL;
$cols = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name='subscription_plans' ORDER BY ordinal_position");
echo implode(' | ', array_column($cols, 'column_name')) . PHP_EOL . PHP_EOL;

$plans = DB::table('subscription_plans')->get();
foreach ($plans as $pl) {
    $arr = (array)$pl;
    foreach ($arr as $k => $v) {
        if ($k !== 'metadata_json') {
            echo "  $k: $v" . PHP_EOL;
        }
    }
    $meta = json_decode($arr['metadata_json'] ?? '{}', true);
    echo "  [meta] " . json_encode($meta, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    echo "---" . PHP_EOL;
}

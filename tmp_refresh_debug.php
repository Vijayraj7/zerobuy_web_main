<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$orderId = isset($argv[1]) ? (int) $argv[1] : 145;
$order = App\Models\Order::find($orderId);

if (! $order) {
    echo "order-not-found\n";
    exit(0);
}

try {
    app(App\Services\Delivery\OrderDeliveryStatusRefreshService::class)->refreshIfEligible($order);
    $order->refresh();

    echo "ok\n";
    echo "order_id=" . $order->id . "\n";
    echo "api_provider=" . (string) ($order->api_provider ?? '') . "\n";
    echo "order_status=" . (string) ($order->order_status?->value ?? $order->order_status ?? '') . "\n";
    echo "provider_status=" . (string) ($order->provider_current_status ?? '') . "\n";
    echo "awb=" . (string) ($order->provider_awb_code ?? '') . "\n";
    echo "track_url=" . (string) ($order->track_url ?? '') . "\n";
} catch (Throwable $e) {
    echo "error\n";
    echo get_class($e) . "\n";
    echo $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
    exit(1);
}

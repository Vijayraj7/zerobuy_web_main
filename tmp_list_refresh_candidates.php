<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$orders = App\Models\Order::query()
    ->select(['id', 'api_provider', 'order_status', 'provider_current_status', 'provider_awb_code', 'provider_order_id', 'provider_shipment_id', 'shiprocket_order_id', 'shiprocket_shipment_id', 'updated_at'])
    ->where(function ($q) {
        $q->whereIn('api_provider', ['delhivery', 'shiprocket'])
            ->orWhereNotNull('provider_awb_code')
            ->orWhereNotNull('provider_order_id')
            ->orWhereNotNull('provider_shipment_id')
            ->orWhereNotNull('shiprocket_order_id')
            ->orWhereNotNull('shiprocket_shipment_id');
    })
    ->latest('id')
    ->limit(30)
    ->get();

foreach ($orders as $order) {
    $status = (string) ($order->order_status?->value ?? $order->order_status ?? '');
    echo implode('|', [
        $order->id,
        (string) ($order->api_provider ?? ''),
        $status,
        (string) ($order->provider_current_status ?? ''),
        (string) ($order->provider_awb_code ?? ''),
        (string) ($order->provider_order_id ?? ''),
        (string) ($order->provider_shipment_id ?? ''),
    ]) . PHP_EOL;
}

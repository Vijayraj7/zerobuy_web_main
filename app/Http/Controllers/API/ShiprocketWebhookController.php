<?php

namespace App\Http\Controllers\API;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShiprocketWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();

        $statusSource = $this->extractFirstString($payload, [
            'current_status',
            'status',
            'shipment_status',
            'awb_status',
            'data.current_status',
            'data.status',
            'data.shipment_status',
            'data.awb_status',
        ]);

        if (! $statusSource) {
            $statusSource = $this->extractFirstString($payload, [
                'scans.0.activity',
                'data.scans.0.activity',
            ]);
        }

        $mappedStatus = $this->mapShiprocketStatusToOrderStatus($statusSource);

        $order = $this->resolveOrderFromPayload($payload);

        if (! $order) {
            Log::info('Shiprocket webhook: order not resolved', [
                'status' => $statusSource,
                'payload' => $payload,
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Webhook received (order not matched)',
            ]);
        }

        if (! $mappedStatus) {
            Log::info('Shiprocket webhook: status not mapped', [
                'order_id' => $order->id,
                'status' => $statusSource,
                'payload' => $payload,
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Webhook received (status ignored)',
            ]);
        }

        if (in_array($order->order_status?->value, [OrderStatus::DELIVERED->value, OrderStatus::CANCELLED->value], true)
            && $order->order_status?->value !== $mappedStatus->value) {
            return response()->json([
                'ok' => true,
                'message' => 'Webhook received (terminal status retained)',
            ]);
        }

        $statusChanged = false;

        if ($order->order_status?->value !== $mappedStatus->value) {
            $order->update([
                'order_status' => $mappedStatus->value,
            ]);
            $statusChanged = true;
        }

        OrderStatusTimeline::updateOrCreate(
            [
                'order_id' => $order->id,
                'status' => $mappedStatus->value,
            ],
            [
                'changed_at' => now(),
            ]
        );

        Log::info('Shiprocket webhook processed', [
            'order_id' => $order->id,
            'from_status' => $order->getOriginal('order_status'),
            'to_status' => $mappedStatus->value,
            'changed' => $statusChanged,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Webhook processed',
        ]);
    }

    private function resolveOrderFromPayload(array $payload): ?Order
    {
        $shipmentId = $this->extractFirstString($payload, [
            'shipment_id',
            'data.shipment_id',
        ]);

        $awbCode = $this->extractFirstString($payload, [
            'awb_code',
            'awb',
            'data.awb_code',
            'data.awb',
        ]);

        $shiprocketOrderId = $this->extractFirstString($payload, [
            'order_id',
            'shiprocket_order_id',
            'sr_order_id',
            'data.order_id',
            'data.shiprocket_order_id',
            'data.sr_order_id',
        ]);

        $channelOrderId = $this->extractFirstString($payload, [
            'channel_order_id',
            'reference_id',
            'data.channel_order_id',
            'data.reference_id',
        ]);

        if ($shipmentId) {
            $order = Order::query()->where('shiprocket_shipment_id', $shipmentId)->first();
            if ($order) {
                return $order;
            }
        }

        if ($awbCode) {
            $order = Order::query()->where('shiprocket_awb_code', $awbCode)->first();
            if ($order) {
                return $order;
            }
        }

        if ($shiprocketOrderId) {
            $order = Order::query()->where('shiprocket_order_id', $shiprocketOrderId)->first();
            if ($order) {
                return $order;
            }
        }

        if ($channelOrderId) {
            $normalizedOrderCode = ltrim($channelOrderId, '#');

            return Order::query()
                ->whereRaw("CONCAT(prefix, order_code) = ?", [$normalizedOrderCode])
                ->first();
        }

        return null;
    }

    private function extractFirstString(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = data_get($payload, $key);

            if ($value !== null && $value !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function mapShiprocketStatusToOrderStatus(?string $status): ?OrderStatus
    {
        if (! $status) {
            return null;
        }

        $normalized = strtoupper(trim((string) $status));
        $normalized = str_replace(['-', ' '], '_', $normalized);

        if (str_contains($normalized, 'DELIVERED')) {
            return OrderStatus::DELIVERED;
        }

        if (str_contains($normalized, 'CANCEL') || str_contains($normalized, 'RTO') || str_contains($normalized, 'LOST') || str_contains($normalized, 'UNDELIVER')) {
            return OrderStatus::CANCELLED;
        }

        if (str_contains($normalized, 'SHIPPED')
            || str_contains($normalized, 'IN_TRANSIT')
            || str_contains($normalized, 'OUT_FOR_DELIVERY')
            || str_contains($normalized, 'PICKED')
            || str_contains($normalized, 'MANIFEST')
            || str_contains($normalized, 'AWB')
            || str_contains($normalized, 'DISPATCH')) {
            return OrderStatus::SHIPPED;
        }

        if (str_contains($normalized, 'NEW') || str_contains($normalized, 'UNSHIPPED') || str_contains($normalized, 'BOOKED') || str_contains($normalized, 'READY')) {
            return OrderStatus::CONFIRM;
        }

        return null;
    }
}

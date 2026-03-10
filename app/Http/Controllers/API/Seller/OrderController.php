<?php

namespace App\Http\Controllers\API\Seller;

use App\Enums\PaymentMethod;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderIdRequest;
use App\Http\Requests\StatusUpdateRequest;
use App\Http\Resources\SellerOrderResource;
use App\Models\Order;
use App\Models\OrderStatusTimeline;
use App\Models\Payment;
use App\Models\Shop;
use App\Repositories\NotificationRepository;
use App\Repositories\OrderRepository;
use App\Services\Delivery\DelhiveryOrderSyncService;
use App\Services\Delivery\ShiprocketOrderSyncService;
use App\Services\NotificationServices;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->page ?? 1;
        $perPage = $request->per_page ?? 15;
        $skip = ($page * $perPage) - $perPage;

        $search = $request->search;

        $string = $search;

        // remove # and 2 letters from search
        if (preg_match('/\d/', $string) && ! preg_match('/\s/', $string) && strpos($string, '#') !== false) {
            $search = substr($string, 3);
        }

        $startDate = $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d') : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->format('Y-m-d') : null;

        $filterType = $request->filter_type ?? null;

        $orderStatus = $request->order_status ?? null;
        $shop = generaleSetting('shop');

        $orders = $shop->orders()->when($search, function ($query) use ($search) {
            return $query->where('order_code', 'like', "%$search%")->orWhereHas('customer', function ($query) use ($search) {
                $query->whereHas('user', function ($query) use ($search) {
                    return $query->where('name', 'like', "%$search%")->orWhere('email', 'like', "%$search%")->orWhere('phone', 'like', "%$search%");
                });
            });
        })->when($startDate, function ($query) use ($startDate, $endDate) {
            return $query->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])->orWhereBetween('updated_at', [$startDate, $endDate]);
            });
        })->when($filterType == 'today', function ($query) {
            return $query->where(function ($query) {
                $query->whereDate('created_at', Carbon::today())->orWhereDate('updated_at', Carbon::today());
            });
        })->when($filterType == 'this_week', function ($query) {
            return $query->where(function ($query) {
                return $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->orWhereBetween('updated_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            });
        })->when($filterType == 'this_month', function ($query) {
            return $query->where(function ($query) {
                $query->whereMonth('created_at', Carbon::now()->month)->orWhereMonth('updated_at', Carbon::now()->month);
            });
        })->when($filterType == 'this_year', function ($query) {
            return $query->where(function ($query) {
                $query->whereYear('created_at', Carbon::now()->year)->orWhereYear('updated_at', Carbon::now()->year);
            });
        })->when($filterType == 'last_week', function ($query) {
            return $query->where(function ($query) {
                $query->whereBetween('created_at', [Carbon::now()->subWeek(), Carbon::now()->subWeek(1)])->orWhereBetween('updated_at', [Carbon::now()->subWeek(), Carbon::now()->subWeek(1)]);
            });
        })->when($filterType == 'last_month', function ($query) {
            return $query->where(function ($query) {
                $query->whereMonth('created_at', Carbon::now()->subMonth()->month)->orWhereMonth('updated_at', Carbon::now()->subMonth()->month);
            });
        })->when($filterType == 'last_year', function ($query) {
            return $query->where(function ($query) {
                $query->whereYear('created_at', Carbon::now()->subYear()->year)->orWhereYear('updated_at', Carbon::now()->subYear()->year);
            });
        })->when($orderStatus == 'pending', function ($query) {
            return $query->where('order_status', OrderStatus::PENDING->value);
        })->when($orderStatus == 'confirm', function ($query) {
            return $query->where('order_status', OrderStatus::CONFIRM->value);
        })->when($orderStatus == 'to_pickup', function ($query) {
            return $query->whereHas('driverOrder')->where(function ($query) {
                $query->where('order_status', OrderStatus::CONFIRM->value)->orWhere('order_status', OrderStatus::PENDING->value);
            });
        })->when($orderStatus == 'to_delivery', function ($query) {
            return $query->where(function ($query) {
                $query->where('order_status', OrderStatus::SHIPPED->value)->orWhere('order_status', OrderStatus::CONFIRM->value);
            });
        })->when($orderStatus == 'delivered', function ($query) {
            return $query->where('order_status', OrderStatus::DELIVERED->value);
        });

        $total = $orders->count();

        $allOrderLists = $orders->latest('id')->skip($skip)->take($perPage)->get();

        $shiprocketSyncService = app(ShiprocketOrderSyncService::class);
        foreach ($allOrderLists as $orderItem) {
            if ((filled($orderItem->shiprocket_order_id) || filled($orderItem->shiprocket_shipment_id)) && blank($orderItem->shiprocket_awb_code)) {
                try {
                    if ($shiprocketSyncService->refreshAwbAndTrackUrl($orderItem)) {
                        $orderItem->refresh();
                    }
                } catch (\Throwable $e) {
                    Log::warning('Shiprocket AWB refresh failed on seller order list fetch (API)', [
                        'order_id' => $orderItem->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        // $pending = $shop->orders()->where(function ($query) {
        //     return $query->where('order_status', OrderStatus::PENDING->value);
        // })->count();

        // $confirm = $shop->orders()->where('order_status', OrderStatus::CONFIRM->value)->count();

        // $toPickup = $shop->orders()->whereHas('driverOrder')->where(function ($query) {
        //     return $query->where('order_status', OrderStatus::CONFIRM->value)->orWhere('order_status', OrderStatus::PROCESSING->value);
        // })->count();

        // $toDelivery = $shop->orders()->where(function ($query) {
        //     return $query->where('order_status', OrderStatus::PICKUP->value)->orWhere('order_status', OrderStatus::ON_THE_WAY->value);
        // })->count();

        // $delivered = $shop->orders()->where(function ($query) {
        //     return $query->where('order_status', OrderStatus::DELIVERED->value);
        // })->count();

        $statuses = $shop->orders()
            ->selectRaw('
                COUNT(CASE WHEN order_status = ? THEN 1 END) as pending,
                COUNT(CASE WHEN order_status = ? THEN 1 END) as confirm,
                COUNT(CASE WHEN order_status IN (?, ?) AND EXISTS (
                    SELECT 1 FROM driver_orders WHERE driver_orders.order_id = orders.id
                ) THEN 1 END) as toPickup,
                COUNT(CASE WHEN order_status IN (?, ?) THEN 1 END) as toDelivery,
                COUNT(CASE WHEN order_status = ? THEN 1 END) as delivered
            ', [
                OrderStatus::PENDING->value,
                OrderStatus::CONFIRM->value,
                OrderStatus::CONFIRM->value,
                OrderStatus::PENDING->value,
                OrderStatus::CONFIRM->value,
                OrderStatus::SHIPPED->value,
                OrderStatus::DELIVERED->value,
            ])->first();

        $pending = (int)$statuses->pending;
        $confirm = (int)$statuses->confirm;
        $toPickup = (int)$statuses->toPickup;
        $toDelivery = (int)$statuses->toDelivery;
        $delivered = (int)$statuses->delivered;

        $totalOrders = $shop->orders->count();

        $statusArray = [
            (object) [
                'name' => 'All',
                'value' => $totalOrders,
                'status' => 'all',
            ],
            (object) [
                'name' => 'Pending',
                'value' => $pending,
                'status' => 'pending',
            ],
            (object) [
                'name' => 'Confirm',
                'value' => $confirm,
                'status' => 'confirm',
            ],
            (object) [
                'name' => 'To Pickup',
                'value' => $toPickup,
                'status' => 'to_pickup',
            ],
            (object) [
                'name' => 'To Delivery',
                'value' => $toDelivery,
                'status' => 'to_delivery',
            ],
            (object) [
                'name' => 'Delivered',
                'value' => $delivered,
                'status' => 'delivered',
            ],
        ];

        return $this->json('all order list', [
            'total_items' => $total,
            'status_orders' => $statusArray,
            'orders' => SellerOrderResource::collection($allOrderLists),
        ]);
    }

    // show order details
    public function show(OrderIdRequest $request)
    {
        $order = Order::find($request->order_id);

        if ($order && (filled($order->shiprocket_order_id) || filled($order->shiprocket_shipment_id)) && blank($order->shiprocket_awb_code)) {
            try {
                $service = app(ShiprocketOrderSyncService::class);
                if ($service->refreshAwbAndTrackUrl($order)) {
                    $order->refresh();
                }
            } catch (\Throwable $e) {
                Log::warning('Shiprocket AWB refresh failed on seller order details fetch (API)', [
                    'order_id' => $order?->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if ($order && (filled($order->shiprocket_order_id) || filled($order->shiprocket_shipment_id))) {
            try {
                $service = app(ShiprocketOrderSyncService::class);
                if ($service->refreshCurrentStatus($order)) {
                    $order->refresh();
                }
            } catch (\Throwable $e) {
                Log::warning('Shiprocket status refresh failed on seller order details fetch (API)', [
                    'order_id' => $order?->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $this->json('Order details', [
            'order' => SellerOrderResource::make($order),
        ]);
    }

    // status update
    public function update(StatusUpdateRequest $request)
    {
        $order = OrderRepository::find($request->order_id);

        if (! $order) {
            return $this->json('Sorry, this order is not found', [], 422);
        }

        $normalizedStatus = strtolower(trim((string) $request->order_status));

        if (in_array($normalizedStatus, ['payment_successful', 'payment successful'], true)) {
            return $this->json('Payment Successful is updated automatically after payment.', [], 422);
        }

        $orderStatus = match ($normalizedStatus) {
            'cancel'  => OrderStatus::CANCELLED->value,
            'shipped' => OrderStatus::SHIPPED->value,
            'delivered' => OrderStatus::DELIVERED->value,
            'ready_to_payment', 'ready to payment' => OrderStatus::READY_TO_PAYMENT->value,
            default   => OrderStatus::CONFIRM->value,
        };

        $isOnlineOrder = $order->payment_method !== PaymentMethod::CASH;
        if ($orderStatus === OrderStatus::READY_TO_PAYMENT->value && ! $isOnlineOrder) {
            return $this->json('Ready to Payment is allowed only for online payment orders.', [], 422);
        }

        if ($orderStatus === OrderStatus::READY_TO_PAYMENT->value && $order->order_status->value !== OrderStatus::PENDING->value) {
            return $this->json('Ready to Payment can be set only when order is Pending.', [], 422);
        }

        if ($orderStatus === OrderStatus::CANCELLED->value) {
            $payment = $order->payments()->latest('payments.id')->first();

            $isOnlineOrder = $order->payment_method !== PaymentMethod::CASH;
            $isPaid = (bool) ($payment?->is_paid ?? false);
            $hasRazorpayOrderId = ! empty($payment?->razorpay_order_id);
            $isPaidOnline = $isOnlineOrder && $isPaid && $hasRazorpayOrderId;

            if ($isPaidOnline) {
                $refund = $this->refundRazorpayPaymentForOrder($order, $payment);
                if (! $refund['status']) {
                    return $this->json(
                        'Unable to cancel order because refund failed. ' . $refund['message'],
                        [],
                        422
                    );
                }
            }
        }

        $order->update([
            'order_status' => $orderStatus,
        ]);

        $orderProvider = strtolower(trim((string) ($order->api_provider ?: $order->shop?->deliverySetting?->delivery_provider ?: '')));

        if ($orderStatus === OrderStatus::CONFIRM->value && in_array($orderProvider, ['shiprocket', 'delhivery'], true)) {
            if ($order->api_provider !== $orderProvider) {
                $order->update(['api_provider' => $orderProvider]);
            }

            if ($orderProvider === 'shiprocket' && empty($order->provider_order_id) && empty($order->shiprocket_order_id)) {
                try {
                    $service = app(ShiprocketOrderSyncService::class);
                    $service->sync($order);
                    $order->refresh();
                } catch (\Throwable $e) {
                    Log::warning('Shiprocket sync failed on seller order accept (API)', [
                        'order_id' => $order->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            if ($orderProvider === 'delhivery') {
                try {
                    if (empty($order->provider_order_id)) {
                        $service = app(DelhiveryOrderSyncService::class);
                        $service->sync($order);
                        $order->refresh();
                    }
                } catch (\Throwable $e) {
                    Log::warning('Delhivery order create failed on seller order accept (API)', [
                        'order_id' => $order->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        // if ($orderStatus === OrderStatus::SHIPPED->value && empty($order->shiprocket_awb_code)) {
        //     try {
        //         $service = app(ShiprocketOrderSyncService::class);

        //         if (empty($order->shiprocket_order_id)) {
        //             $service->sync($order);
        //             $order->refresh();
        //         }

        //         $service->requestPickup($order);
        //         $order->refresh();
        //         $service->refreshTrackingUrl($order);
        //     } catch (\Throwable $e) {
        //         Log::warning('Shiprocket pickup request failed on seller order shipped (API)', [
        //             'order_id' => $order->id,
        //             'message' => $e->getMessage(),
        //         ]);
        //     }
        // }

        OrderStatusTimeline::updateOrCreate(
            [
                'order_id' => $order->id,
                'status' => $orderStatus,
            ],
            [
                'changed_at' => Carbon::now(),
            ]
        );

        $title = 'Order status ' . $orderStatus;
        $message = 'Your order ' . $orderStatus . ' order id: ' . $order->prefix . $order->order_code;
        $deviceKeys = $order->customer->user->devices->pluck('key')->toArray();

        $noty = null;
        try {
            $noty =  NotificationServices::sendNotification($message, $deviceKeys, $title);
        } catch (\Throwable $th) {
        }

        $notify = (object) [
            'title' => $title,
            'content' => $message,
            'user_id' => $order->customer->user_id,
            'type' => 'order',
        ];
        NotificationRepository::storeByRequest($notify);

        $order->refresh();

        // OrderMailEvent::dispatch($order);

        return $this->json('Order status updated successfully!', [
            'noty' => $noty,
            'order' => SellerOrderResource::make($order),
        ]);
    }

    // track url update
    public function trackUrlUpdate(StatusUpdateRequest $request)
    {
        $order = OrderRepository::find($request->order_id);

        if (! $order) {
            return $this->json('Sorry, this order is not found', [], 422);
        }

        $order->update([
            'track_url' => $request->track_url,
        ]);

        $order->refresh();

        // OrderMailEvent::dispatch($order);

        return $this->json('Track url updated successfully!', [
            'order' => SellerOrderResource::make($order),
        ]);
    }

    // delivery charge update
    public function deliveryChargeUpdate(StatusUpdateRequest $request)
    {
        $order = OrderRepository::find($request->order_id);

        if (! $order) {
            return $this->json('Sorry, this order is not found', [], 422);
        }

        $order->update([
            'delivery_charge' => $request->delivery_charge,
        ]);

        $order->refresh();

        // OrderMailEvent::dispatch($order);

        return $this->json('Delivery Charge updated successfully!', [
            'order' => SellerOrderResource::make($order),
        ]);
    }

    private function getShopRazorpayConfig(Shop $shop): array
    {
        if (! $shop->online_payment_enabled) {
            return [
                'status' => false,
                'message' => 'Online payment is not enabled for this shop',
            ];
        }

        if (($shop->online_payment_provider ?? '') !== 'razorpay') {
            return [
                'status' => false,
                'message' => 'This shop does not support Razorpay',
            ];
        }

        $key = data_get($shop->online_payment_config, 'razorpay.key_id');
        $secret = data_get($shop->online_payment_config, 'razorpay.key_secret');

        if (! $key || ! $secret) {
            return [
                'status' => false,
                'message' => 'Razorpay keys are missing for this shop',
            ];
        }

        return [
            'status' => true,
            'key' => $key,
            'secret' => $secret,
        ];
    }

    private function refundRazorpayPaymentForOrder(Order $order, Payment $payment): array
    {
        if (! empty($payment->razorpay_refund_id)) {
            return [
                'status' => true,
                'message' => 'Payment already refunded',
            ];
        }

        $shop = $order->shop;
        if (! $shop) {
            return [
                'status' => false,
                'message' => 'Shop not found for refund',
            ];
        }

        $config = $this->getShopRazorpayConfig($shop);
        if (! ($config['status'] ?? false)) {
            return [
                'status' => false,
                'message' => $config['message'] ?? 'Razorpay config not found for refund',
            ];
        }

        $razorpayPaymentId = trim((string) ($payment->razorpay_payment_id ?? ''));
        if ($razorpayPaymentId === '') {
            return [
                'status' => false,
                'message' => 'Razorpay payment id missing for refund',
            ];
        }

        $amountInPaise = (int) round(((float) $payment->amount) * 100);

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->withBasicAuth($config['key'], $config['secret'])
                ->post('https://api.razorpay.com/v1/payments/' . $razorpayPaymentId . '/refund', [
                    'amount' => $amountInPaise,
                    'notes' => [
                        'order_id' => (string) $order->id,
                        'reason' => 'Seller cancellation',
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('Razorpay refund failed on seller cancel (API)', [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'status' => false,
                    'message' => 'Razorpay refund request failed',
                ];
            }

            $refundId = (string) ($response->json('id') ?? '');
            $refundStatus = (string) ($response->json('status') ?? '');
            $refundAmount = $response->json('amount');

            if ($refundId === '') {
                return [
                    'status' => false,
                    'message' => 'Invalid Razorpay refund response',
                ];
            }

            $payment->update([
                'razorpay_refund_id' => $refundId,
                'razorpay_refund_status' => $refundStatus !== '' ? $refundStatus : 'processed',
                'razorpay_refund_amount' => is_numeric($refundAmount)
                    ? ((float) $refundAmount / 100)
                    : (float) $payment->amount,
                'razorpay_refunded_at' => now(),
            ]);

            return [
                'status' => true,
                'message' => 'Refund initiated successfully',
            ];
        } catch (\Throwable $e) {
            Log::warning('Razorpay refund exception on seller cancel (API)', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'Razorpay refund exception',
            ];
        }
    }
}

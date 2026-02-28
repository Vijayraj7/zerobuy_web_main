<?php

namespace App\Http\Controllers\API;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Http\Resources\OrderDetailsResource;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderStatusTimeline;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shop;
use App\Models\VerifyManage;
use App\Repositories\OrderRepository;
use App\Services\Delivery\ShiprocketOrderSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Razorpay\Api\Api;

class OrderController extends Controller
{
    private const LEGACY_CANCELLED_BY_CUSTOMER = 'Cancelled by Customer';

    /**
     * Display a listing of the orders with status filter and pagination options.
     *
     * @param  Request  $request  The HTTP request
     * @return Some_Return_Value json Response
     *
     * @throws Some_Exception_Class If something goes wrong
     */
    public function index(Request $request)
    {
        $orderStatus = $request->order_status;

        $page = $request->page;
        $perPage = $request->per_page;
        $skip = ($page * $perPage) - $perPage;

        $customer = auth()->user()->customer;

        $orders = $customer->orders()->when($orderStatus, function ($query) use ($orderStatus) {
            if ($orderStatus === OrderStatus::CANCELLED->value || $orderStatus === OrderStatus::CANCELLED_BY_CUSTOMER->value) {
                return $query->whereIn('order_status', [
                    OrderStatus::CANCELLED->value,
                    OrderStatus::CANCELLED_BY_CUSTOMER->value,
                    self::LEGACY_CANCELLED_BY_CUSTOMER,
                ]);
            }

            return $query->where('order_status', $orderStatus);
        })->latest('id');

        $total = $orders->count();

        // paginate
        $orders = $orders->when($perPage && $page, function ($query) use ($perPage, $skip) {
            return $query->skip($skip)->take($perPage);
        })->get();

        $shiprocketSyncService = app(ShiprocketOrderSyncService::class);
        foreach ($orders as $orderItem) {
            if ((filled($orderItem->shiprocket_order_id) || filled($orderItem->shiprocket_shipment_id)) && blank($orderItem->shiprocket_awb_code)) {
                try {
                    if ($shiprocketSyncService->refreshAwbAndTrackUrl($orderItem)) {
                        $orderItem->refresh();
                    }
                } catch (\Throwable $e) {
                    Log::warning('Shiprocket AWB refresh failed on customer order list fetch (API)', [
                        'order_id' => $orderItem->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        // status wise orders
        $statusWiseOrders = collect(OrderStatus::cases())->mapWithKeys(function ($status) use ($customer) {
            return [$status->value => $customer->orders()->where('order_status', $status->value)->count()];
        });

        // Response
        return $this->json('orders', [
            'total' => $total,
            'status_wise_orders' => [
                'all' => $customer->orders()->count(),
                'pending' => $statusWiseOrders[OrderStatus::PENDING->value],
                'confirm' => $statusWiseOrders[OrderStatus::CONFIRM->value],
                'processing' => $statusWiseOrders[OrderStatus::PENDING->value],
                'pickup' => $statusWiseOrders[OrderStatus::SHIPPED->value],
                'on_the_way' => $statusWiseOrders[OrderStatus::SHIPPED->value],
                'delivered' => $statusWiseOrders[OrderStatus::DELIVERED->value],
                'cancelled' => ($statusWiseOrders[OrderStatus::CANCELLED->value] ?? 0)
                    + ($statusWiseOrders[OrderStatus::CANCELLED_BY_CUSTOMER->value] ?? 0)
                    + $customer->orders()->where('order_status', self::LEGACY_CANCELLED_BY_CUSTOMER)->count(),
            ],
            'orders' => OrderResource::collection($orders),
        ]);
    }

    /**
     * Store a newly created order in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(OrderRequest $request)
    {
        $timerStart = microtime(true);
        $elapsedMs = static fn () => (int) round((microtime(true) - $timerStart) * 1000);

        // return $this->json('Order successfully', [
        //     'request' => Address::find($request->address_id),
        // ], 500);

        $isBuyNow = $request->is_buy_now ?? false;
        $user = auth()->user();
        // $rshop = generaleSetting('shop',$user);

        $verifyManage = Cache::rememberForever('verify_manage', function () {
            return VerifyManage::first();
        });

        $accountVerified = false;
        if ($user->email_verified_at || $user->phone_verified_at) {
            $accountVerified = true;
        }

        if ($verifyManage?->order_place_account_verify && ! $accountVerified) {
            return $this->json('Please verify your account first. without verify account you can not place order', [], 422);
        }

        $carts = $user->customer?->carts()->whereIn('shop_id', $request->shop_ids)->where('is_buy_now', $isBuyNow)->get();

        Log::info('place-order carts loaded', [
            'user_id' => $user->id,
            'payment_method' => (string) $request->payment_method,
            'shop_ids' => $request->shop_ids,
            'cart_count' => $carts->count(),
            'elapsed_ms' => $elapsedMs(),
        ]);

        if ($carts->isEmpty()) {
            return $this->json('Sorry shop cart is empty', [], 422);
        }

        $is_valid = true;
        $min_amount = 0;
        $shop_name = '';
        $totalPayableAmountx = 0;
        $shopProducts = $carts->groupBy('shop_id');

        $requestedPaymentMethod = strtolower(trim((string) $request->payment_method));
        $isOnlineCheckout = $requestedPaymentMethod !== 'cash';

        if ($isOnlineCheckout && $shopProducts->count() > 1) {
            return $this->json('For online payment, please place order from one shop at a time.', [], 422);
        }

        foreach ($shopProducts as $shopId => $cartProducts) {
            $shop = Shop::find($shopId);

            if (! $shop) {
                return $this->json('Shop not found.', [], 422);
            }

            if ($requestedPaymentMethod === 'cash' && ! ($shop->cash_on_delivery_enabled ?? true)) {
                return $this->json('Cash on Delivery is disabled for '.$shop->name, [], 422);
            }

            $getCartAmounts = OrderRepository::getCartWiseAmounts($shop, collect($cartProducts), $request->coupon_code, $request->address_id);
            $current_amount = $getCartAmounts['payableAmount'];
            $totalPayableAmountx += $getCartAmounts['payableAmount'];
            if ($current_amount < $shop->min_order_amount) {
                $is_valid = false;
                $shop_name = $shop->name;
                $min_amount = $shop->min_order_amount;;
            }
        }

        Log::info('place-order amount checks done', [
            'user_id' => $user->id,
            'payment_method' => (string) $request->payment_method,
            'shop_count' => $shopProducts->count(),
            'elapsed_ms' => $elapsedMs(),
        ]);
        // return $this->json(count($shopProducts) . 'Sorry, your cart total amount less than minimum order amount ' . $shop->min_order_amount, [], 422);
        if (!$is_valid) {
            return $this->json('Sorry, minimum order amount is ' . $min_amount . ' in ' . $shop_name, [], 422);
        }

        $paymentMethod = collect(PaymentMethod::cases())->first(function (PaymentMethod $case) use ($request) {
            return $case->name === strtoupper((string) $request->payment_method);
        });

        if (! $paymentMethod) {
            return $this->json('Invalid payment method selected.', [], 422);
        }

        if ($paymentMethod->name === 'RAZORPAY') {
            $shopId = (int) $shopProducts->keys()->first();
            $shop = Shop::find($shopId);

            if (! $shop) {
                return $this->json('Shop not found for payment.', [], 422);
            }

            $config = $this->getShopRazorpayConfig($shop);
            if (! $config['status']) {
                return $this->json($config['message'], [], 422);
            }
        }

        $paymentUrl = null;
        if ($paymentMethod->name === 'RAZORPAY') {
            $shopId = (int) $shopProducts->keys()->first();
            $shop = Shop::find($shopId);

            if (! $shop) {
                return $this->json('Shop not found for payment.', [], 422);
            }

            $intentToken = Str::uuid()->toString();

            $payment = Payment::create([
                'amount' => (float) $totalPayableAmountx,
                'currency' => 'INR',
                'payment_method' => 'razorpay',
                'is_paid' => false,
                'payment_token' => $intentToken,
            ]);

            Log::info('place-order payment intent created', [
                'user_id' => $user->id,
                'payment_id' => $payment->id,
                'payment_method' => $paymentMethod->name,
                'elapsed_ms' => $elapsedMs(),
            ]);

            $razorpayData = $this->createShopRazorpayOrder($payment, $shop);

            Log::info('place-order razorpay order attempt finished', [
                'payment_id' => $payment->id,
                'status' => $razorpayData['status'] ?? false,
                'elapsed_ms' => $elapsedMs(),
            ]);

            if (! $razorpayData['status']) {
                $payment->delete();
                return $this->json('Razorpay order creation failed', [
                    'error' => $razorpayData['message'],
                ], 422);
            }

            Cache::put('payment_intent:'.$intentToken, [
                'user_id' => $user->id,
                'address_id' => (int) $request->address_id,
                'coupon_code' => $request->coupon_code,
                'note' => $request->note,
                'payment_method' => 'razorpay',
                'shop_ids' => array_map('intval', (array) $request->shop_ids),
                'is_buy_now' => $isBuyNow,
                'gst' => $request->gst,
            ], now()->addMinutes(30));

            return $this->json('Order created successfully', [
                'payment_flow' => 'razorpay',
                'order_payment_url' => null,
                'razorpay' => $razorpayData['data'],
            ]);
        }

        // Store the order for non-razorpay payments
        try {
            $payment = OrderRepository::storeByRequestFromCart($request, $paymentMethod, $carts);
        } catch (\Throwable $e) {
            return $this->json($e->getMessage() ?: 'Unable to place order right now.', [], 422);
        }

        Log::info('place-order payment stored', [
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'payment_method' => $paymentMethod->name,
            'elapsed_ms' => $elapsedMs(),
        ]);

        if ($paymentMethod->name != 'CASH') {
            $paymentUrl = route('order.payment', ['payment' => $payment, 'gateway' => $request->payment_method]);
        }


        return $this->json('Order created successfully', [
            'payment_flow' => $paymentMethod->name == 'CASH' ? 'cash' : 'redirect',
            'order_payment_url' => $paymentUrl,
        ]);
    }

    public function verifyRazorpayPayment(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $payment = Payment::with(['orders.shop'])->findOrFail($request->payment_id);

        if ($payment->is_paid) {
            return $this->json('Payment already verified', [
                'payment_id' => $payment->id,
            ]);
        }

        $intent = null;
        if (! empty($payment->payment_token)) {
            $intent = Cache::get('payment_intent:'.$payment->payment_token);
        }

        $shop = $payment->orders->first()?->shop;
        if (! $shop && ! empty($intent['shop_ids']) && is_array($intent['shop_ids'])) {
            $shop = Shop::find((int) $intent['shop_ids'][0]);
        }
        if (! $shop) {
            return $this->json('Shop not found for this payment', [], 422);
        }

        $config = $this->getShopRazorpayConfig($shop);
        if (! $config['status']) {
            return $this->json($config['message'], [], 422);
        }

        if (! empty($payment->razorpay_order_id) && $payment->razorpay_order_id !== $request->razorpay_order_id) {
            return $this->json('Invalid Razorpay order id for this payment', [], 422);
        }

        try {
            $api = new Api($config['key'], $config['secret']);
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ]);

            DB::transaction(function () use ($payment, $request, $intent) {
                if ($payment->orders()->count() === 0) {
                    if (! is_array($intent)) {
                        throw new \RuntimeException('Payment session expired. Please place order again.');
                    }

                    $authUser = auth()->user();
                    if (! $authUser || ((int) ($intent['user_id'] ?? 0) !== (int) $authUser->id)) {
                        throw new \RuntimeException('Invalid payment session user.');
                    }

                    $shopIds = array_map('intval', (array) ($intent['shop_ids'] ?? []));
                    $isBuyNow = (bool) ($intent['is_buy_now'] ?? false);
                    $carts = $authUser->customer?->carts()
                        ->whereIn('shop_id', $shopIds)
                        ->where('is_buy_now', $isBuyNow)
                        ->get();

                    if (! $carts || $carts->isEmpty()) {
                        throw new \RuntimeException('Cart is empty for this payment session.');
                    }

                    OrderRepository::storeByRequestFromCart(
                        tap($request, function ($request) use ($intent, $shopIds, $isBuyNow) {
                            $request->merge([
                                'address_id' => (int) ($intent['address_id'] ?? 0),
                                'coupon_code' => $intent['coupon_code'] ?? null,
                                'note' => $intent['note'] ?? null,
                                'payment_method' => $intent['payment_method'] ?? 'razorpay',
                                'shop_ids' => $shopIds,
                                'is_buy_now' => $isBuyNow,
                                'gst' => $intent['gst'] ?? null,
                            ]);
                        }),
                        PaymentMethod::RAZORPAY,
                        $carts,
                        $payment,
                    );
                }

                $payment->orders()->update([
                    'payment_status' => PaymentStatus::PAID->value,
                ]);

                $payment->update([
                    'is_paid' => true,
                    'razorpay_order_id' => $request->razorpay_order_id,
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_signature' => $request->razorpay_signature,
                ]);
            });

            if (! empty($payment->payment_token)) {
                Cache::forget('payment_intent:'.$payment->payment_token);
            }

            return $this->json('Payment verified successfully', [
                'payment_id' => $payment->id,
                'is_paid' => true,
            ]);
        } catch (\Throwable $e) {
            Log::error('Razorpay payment verification failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return $this->json('Payment verification failed: '.$e->getMessage(), [], 422);
        }
    }

    private function createShopRazorpayOrder(Payment $payment, ?Shop $shop = null): array
    {
        $shop = $shop ?: $payment->orders->first()?->shop;

        if (! $shop) {
            return [
                'status' => false,
                'message' => 'Shop not found for this payment',
            ];
        }

        $config = $this->getShopRazorpayConfig($shop);
        if (! $config['status']) {
            return $config;
        }

        try {
            $amountInPaise = (int) round(((float) $payment->amount) * 100);

            $orderPayload = [
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'receipt' => 'ord_'.$payment->id.'_'.time(),
                'notes' => [
                    'payment_id' => (string) $payment->id,
                    'shop_id' => (string) $shop->id,
                    'purpose' => 'Customer Order Payment',
                ],
            ];

            $response = Http::timeout(12)
                ->acceptJson()
                ->withBasicAuth($config['key'], $config['secret'])
                ->post('https://api.razorpay.com/v1/orders', $orderPayload);

            if (! $response->successful()) {
                Log::warning('Razorpay order creation HTTP failed', [
                    'payment_id' => $payment->id,
                    'shop_id' => $shop->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'status' => false,
                    'message' => 'Unable to create Razorpay order right now. Please try again.',
                ];
            }

            $order = (object) $response->json();

            if (empty($order->id)) {
                return [
                    'status' => false,
                    'message' => 'Invalid Razorpay order response',
                ];
            }

            $payment->update([
                'payment_method' => 'razorpay',
                'razorpay_order_id' => $order->id,
            ]);

            return [
                'status' => true,
                'data' => [
                    'payment_id' => $payment->id,
                    'razorpay_key' => $config['key'],
                    'razorpay_order_id' => $order->id,
                    'amount' => $order->amount,
                    'currency' => $order->currency,
                    'shop_id' => $shop->id,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('Razorpay order creation failed', [
                'payment_id' => $payment->id,
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'Unable to create Razorpay order: '.$e->getMessage(),
            ];
        }
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

    /**
     * Again order
     */
    public function reOrder(Request $request)
    {
        // Validate the request
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $user = auth()->user();

        $verifyManage = Cache::rememberForever('verify_manage', function () {
            return VerifyManage::first();
        });

        $accountVerified = false;
        if ($user->email_verified_at || $user->phone_verified_at) {
            $accountVerified = true;
        }

        if ($verifyManage?->order_place_account_verify && ! $accountVerified) {
            return $this->json('Please verify your account first. without verify account you can not place order', [], 422);
        }

        // Find the order
        $order = Order::find($request->order_id);
        $subscription = null;

        if (! $order->shop->user->hasRole('root')) {
            $generalSetting = generaleSetting('setting');

            if ($generalSetting?->business_based_on == 'subscription') {
                $subscription = $order->shop->currentSubscription;

                if (! $subscription) {
                    return $this->json('Sorry, the shop is not available now', [], 422);
                }
            }
        }

        if ($order->order_status->value == OrderStatus::DELIVERED->value) {

            // Check product quantity
            foreach ($order->products as $product) {
                if ($product->quantity < $product->pivot->quantity) {
                    return $this->json('Sorry, your product quantity out of stock', [], 422);
                }
            }

            // create payment
            $toUpper = strtoupper($request->payment_method ?? $order->payment_method);
            $paymentMethods = PaymentMethod::cases();
            $paymentMethod = $paymentMethods[array_search($toUpper, array_column(PaymentMethod::cases(), 'name'))];

            $payment = Payment::create([
                'amount' => $order->payable_amount,
                'payment_method' => $paymentMethod?->value,
            ]);

            // re-order
            $order = OrderRepository::reOrder($order, $payment);

            if ($subscription) {
                $subscription->update([
                    'remaining_sales' => $subscription->remaining_sales - 1,
                ]);
            }

            // attach payment to order
            $payment->orders()->attach($order->id);

            // payment url
            $paymentUrl = null;
            if ($paymentMethod->name != 'CASH') {
                $paymentUrl = route('order.payment', ['payment' => $payment, 'gateway' => $payment->payment_method]);
            }

            // return
            return $this->json('Re-order created successfully', [
                'order_payment_url' => $paymentUrl,
                'order' => OrderResource::make($order),
            ]);
        }

        return $this->json('Sorry, You can not  re-order because order is not delivered', [], 422);
    }

    /**
     * Show the order details.
     *
     * @param  Request  $request  The request object
     */
    public function show(Request $request)
    {
        // Validate the request
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        // Find the order
        $order = Order::with([
            'products',     // ✅ only this
            'statusTimelines', // Add status timelines
            'payments',
        ])->find($request->order_id);

        if ($order) {
            $this->syncRazorpayPaymentStatusForOrder($order);
            $order->load('payments');
        }

        if ($order && (filled($order->shiprocket_order_id) || filled($order->shiprocket_shipment_id)) && blank($order->shiprocket_awb_code)) {
            try {
                $service = app(ShiprocketOrderSyncService::class);
                if ($service->refreshAwbAndTrackUrl($order)) {
                    $order->refresh();
                }
            } catch (\Throwable $e) {
                Log::warning('Shiprocket AWB refresh failed on customer order details fetch (API)', [
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
                Log::warning('Shiprocket status refresh failed on customer order details fetch (API)', [
                    'order_id' => $order?->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $this->json('order details', [
            'order' => OrderDetailsResource::make($order),
        ]);
    }

    private function syncRazorpayPaymentStatusForOrder(Order $order): void
    {
        $payment = $order->payments()->latest('payments.id')->first();

        if (! $payment || empty($payment->razorpay_order_id)) {
            return;
        }

        if ($payment->is_paid) {
            return;
        }

        $shop = $order->shop;
        if (! $shop) {
            return;
        }

        $config = $this->getShopRazorpayConfig($shop);
        if (! ($config['status'] ?? false)) {
            return;
        }

        try {
            $response = Http::timeout(12)
                ->acceptJson()
                ->withBasicAuth($config['key'], $config['secret'])
                ->get('https://api.razorpay.com/v1/orders/'.$payment->razorpay_order_id.'/payments');

            if (! $response->successful()) {
                Log::warning('Razorpay order payments fetch failed', [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'status' => $response->status(),
                ]);

                return;
            }

            $items = $response->json('items') ?? [];
            if (empty($items) || ! is_array($items)) {
                return;
            }

            $latestPayment = collect($items)->sortByDesc(function ($row) {
                return (int) data_get($row, 'created_at', 0);
            })->first();

            if (! $latestPayment) {
                return;
            }

            $gatewayStatus = strtolower((string) data_get($latestPayment, 'status', ''));
            $gatewayPaymentId = (string) data_get($latestPayment, 'id', '');

            $updates = [];
            if ($gatewayPaymentId !== '' && empty($payment->razorpay_payment_id)) {
                $updates['razorpay_payment_id'] = $gatewayPaymentId;
            }

            if ($gatewayStatus === 'captured') {
                $updates['is_paid'] = true;
            }

            if (! empty($updates)) {
                $payment->update($updates);
            }

            if ($gatewayStatus === 'captured') {
                $payment->orders()->update([
                    'payment_status' => PaymentStatus::PAID->value,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Razorpay payment status sync failed', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cancel the order.
     */
    public function cancel(Request $request)
    {
        // Validate the request
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'cancel_reason' => 'required|string|min:3|max:1000',
        ]);

        // Find the order
        $order = Order::find($request->order_id);

        if ($order->order_status->value == OrderStatus::PENDING->value) {
            $payment = $order->payments()->latest('payments.id')->first();

            $isOnlineOrder = $order->payment_method !== PaymentMethod::CASH;
            $isPaidOnline = $isOnlineOrder && $payment && $payment->is_paid;

            if ($isPaidOnline) {
                $refund = $this->refundRazorpayPaymentForOrder($order, $payment);
                if (! $refund['status']) {
                    return $this->json(
                        'Unable to cancel order because refund failed. '.$refund['message'],
                        [],
                        422
                    );
                }
            }

            // update order status
            $order->update([
                'order_status' => OrderStatus::CANCELLED_BY_CUSTOMER->value,
                'cancel_reason' => (string) $request->cancel_reason,
            ]);

            OrderStatusTimeline::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'status' => OrderStatus::CANCELLED_BY_CUSTOMER->value,
                ],
                [
                    'changed_at' => now(),
                ]
            );

            foreach ($order->products as $product) {
                $qty = $product->pivot->quantity;

                $product->update(['quantity' => $product->quantity + $qty]);

                $flashSale = $product->flashSales?->first();
                $flashSaleProduct = null;

                if ($flashSale) {
                    $flashSaleProduct = $flashSale?->products()->where('id', $product->id)->first();

                    if ($flashSaleProduct && $product->pivot?->price) {
                        if ($flashSaleProduct->pivot->sale_quantity >= $qty && ($product->pivot?->price == $flashSaleProduct->pivot->price)) {
                            $flashSale->products()->updateExistingPivot($product->id, [
                                'sale_quantity' => $flashSaleProduct->pivot->sale_quantity - $qty,
                            ]);
                        }
                    }
                }
            }

            return $this->json('Order cancelled successfully', [
                'order' => OrderResource::make($order),
            ]);
        }

        return $this->json('Sorry, order cannot be cancelled because it is not pending', [], 422);
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
                ->post('https://api.razorpay.com/v1/payments/'.$razorpayPaymentId.'/refund', [
                    'amount' => $amountInPaise,
                    'notes' => [
                        'order_id' => (string) $order->id,
                        'reason' => 'Customer cancellation',
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('Razorpay refund failed', [
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
            Log::warning('Razorpay refund exception', [
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

    public function payment(Order $order, $paymentMethod = null)
    {
        if ($paymentMethod != 'cash' && $paymentMethod != null) {

            $payment = Payment::create([
                'amount' => $order->payable_amount,
                'payment_method' => $paymentMethod,
            ]);

            $payment->orders()->attach($order->id);

            $paymentUrl = route('order.payment', ['payment' => $payment, 'gateway' => $payment->payment_method]);

            return $this->json('Payment created', [
                'order_payment_url' => $paymentUrl,
            ]);

            // $payment = $order->payments()?->first();

            // if ($payment->payment_method != $paymentMethod) {

            //     $order->update([
            //         'payment_method' => $paymentMethod,
            //     ]);

            //     $orders = $payment->orders()->where('order_status', '!=', OrderStatus::CANCELLED->value)->where('payment_status', PaymentStatus::PENDING->value)->get();

            //     $payment->update([
            //         'payment_method' => $paymentMethod,
            //         'amount' => $orders->sum('payable_amount'),
            //     ]);

            //     $payment->orders()->sync($orders);

            //     $paymentUrl = route('order.payment', ['payment' => $payment, 'gateway' => $payment->payment_method]);

            //     return $this->json('Payment created', [
            //         'order_payment_url' => $paymentUrl,
            //         'order' => OrderResource::make($order),
            //     ]);
            // }

            // $payment = Payment::create([
            //     'amount' => $order->payable_amount,
            //     'payment_method' => $paymentMethod,
            // ]);
        }

        return $this->json('Sorry, You can not  re-payment because payment is CASH', [], 422);
    }
}

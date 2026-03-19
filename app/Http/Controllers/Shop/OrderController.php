<?php

namespace App\Http\Controllers\Shop;

use App\Enums\PaymentMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use App\Models\OrderStatusTimeline;
use App\Models\Payment;
use App\Models\Shop;
use App\Repositories\NotificationRepository;
use App\Repositories\OrderRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Services\Delivery\DelhiveryOrderSyncService;
use App\Services\Delivery\OrderDeliveryStatusRefreshService;
use App\Services\Delivery\ShiprocketOrderSyncService;
use App\Services\NotificationServices;
use Carbon\Carbon;
use Endroid\QrCode\QrCode as EndroidQrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Display the order list with filter status.
     */
    public function index($status = null)
    {
        $status = $status ? str_replace('_', ' ', $status) : '';

        return view('shop.order.index', compact('status'));
    }

    public function apiIndex(Request $request)
    {
        $status = $request->get('status');
        $shop = generaleSetting('shop');
        $shopId = $shop?->id;

        if (! $shopId) {
            return $this->json('Order list', ['orders' => []]);
        }

        $orders = Order::query()
            ->where('shop_id', $shopId)
            ->with([
                'customer.user:id,name,phone',
                'orderProducts:id,order_id,product_id,quantity',
                'orderProducts.product.media:id,src',
            ])
            ->when($status, function ($query) use ($status) {
                $query->where('order_status', $status);
            })
            ->latest('id')
            ->get();

        $data = $orders->map(function ($order) {
            $firstProduct = $order->orderProducts?->first()?->product;
            $thumbnail = $firstProduct?->thumbnail ?? asset('default/default.jpg');

            return [
                'id' => $order->id,
                'thumbnail' => $thumbnail,
                'created_at' => $order->created_at?->format('d-m-Y | h:i A') ?? '-',
                'order_id' => ($order->prefix ?? '') . ($order->order_code ?? ''),
                'customer_name' => $order->customer?->user?->name ?? '-',
                'mobile_no' => $order->customer?->user?->phone ?? '-',
                'quantity' => $order->orderProducts?->sum('quantity') ?? 0,
                'total_amount' => showCurrency($order->payable_amount ?? 0),
                'payment_method' => __($order->payment_method?->value ?? '-'),
                'status' => __($order->order_status?->value ?? '-'),
                'details_url' => route('shop.order.show', $order->id),
                'invoice_url' => route('shop.download-invoice', $order->id),
            ];
        })->values();

        return $this->json('Order list', ['orders' => $data]);
    }

    /**
     * Display the order details.
     */
    public function show($orderId)
    {
        $order = Order::whereId($orderId)->firstOrFail()->load('address.stateData', 'address.districtData', 'shop.deliverySetting');

        // Refresh delivery status from provider API if not in a terminal state (throttled, non-blocking)
        $terminalStatuses = [
            OrderStatus::DELIVERED->value,
            OrderStatus::CANCELLED->value,
            OrderStatus::CANCELLED_BY_CUSTOMER->value,
        ];

        if (!in_array((string)($order->order_status?->value ?? ''), $terminalStatuses, true)) {
            try {
                app(OrderDeliveryStatusRefreshService::class)->refreshIfEligible($order);
                $order->refresh();
            } catch (\Throwable $e) {
                Log::warning('Order delivery status refresh failed on shop order details fetch', [
                    'order_id' => $order->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $orderStatus = OrderStatus::cases();

        $riders = Driver::whereHas('user', function ($query) {
            $query->where('is_active', true);
        })->get();

        $deliverySetting = $order->shop->deliverySetting;
        $isManualDelivery = $deliverySetting && $deliverySetting->delivery_mode === 'manual';

        $retryShipProvider = strtolower(trim((string) ($order->api_provider ?: $deliverySetting?->delivery_provider ?: '')));
        $isProviderOrderCreated = false;

        $apiProviderStatus = null;
        $apiProviderStatusLabel = null;
        $apiProviderStatusClass = 'secondary';
        $apiProviderFailureReason = session('provider_ship_error');

        $isApiProviderOrder =
            ($deliverySetting && $deliverySetting->delivery_mode === 'provider_api' && in_array($retryShipProvider, ['shiprocket', 'delhivery'], true))
            || in_array($retryShipProvider, ['shiprocket', 'delhivery'], true);

        if ($retryShipProvider === 'shiprocket') {
            $isProviderOrderCreated = !empty($order->provider_order_id) || !empty($order->shiprocket_order_id);
        } elseif ($retryShipProvider === 'delhivery') {
            $isProviderOrderCreated = !empty($order->provider_order_id);
        }

        if (in_array($retryShipProvider, ['shiprocket', 'delhivery'], true)) {
            $hasProviderOrderId = !empty($order->provider_order_id) || !empty($order->shiprocket_order_id);
            $hasProviderShipmentId = !empty($order->provider_shipment_id) || !empty($order->shiprocket_shipment_id);
            $hasProviderAwb = !empty($order->provider_awb_code) || !empty($order->shiprocket_awb_code);

            // Show actual delivery status from API if available, otherwise show creation status
            $orderStatusValue = (string) ($order->order_status?->value ?? '');
            
            if (in_array($orderStatusValue, [OrderStatus::DELIVERED->value, OrderStatus::SHIPPED->value, OrderStatus::CANCELLED->value], true)) {
                // Show actual delivery status from the provider API
                $apiProviderStatus = strtolower(str_replace(' ', '_', $orderStatusValue));
                $apiProviderStatusLabel = $orderStatusValue;
                
                if ($orderStatusValue === OrderStatus::DELIVERED->value) {
                    $apiProviderStatusClass = 'success';
                } elseif ($orderStatusValue === OrderStatus::SHIPPED->value) {
                    $apiProviderStatusClass = 'info';
                } else { // CANCELLED
                    $apiProviderStatusClass = 'danger';
                }
            } elseif ($hasProviderAwb) {
                $apiProviderStatus = 'awb_generated';
                $apiProviderStatusLabel = 'AWB Generated';
                $apiProviderStatusClass = 'success';
            } elseif ($hasProviderShipmentId) {
                $apiProviderStatus = 'shipment_created';
                $apiProviderStatusLabel = 'Shipment Created';
                $apiProviderStatusClass = 'info';
            } elseif ($hasProviderOrderId) {
                $apiProviderStatus = 'order_created';
                $apiProviderStatusLabel = 'Order Created';
                $apiProviderStatusClass = 'primary';
            } else {
                $apiProviderStatus = 'not_created';
                $apiProviderStatusLabel = 'Not Created';
                $apiProviderStatusClass = 'warning';
            }

        }

        $showRetryShipButton =
            $order->order_status?->value === OrderStatus::CONFIRM->value
            && in_array($retryShipProvider, ['shiprocket', 'delhivery'], true)
            && !$isProviderOrderCreated;

        $showCreateShipmentButton =
            in_array($retryShipProvider, ['shiprocket', 'delhivery'], true)
            && !in_array($order->order_status->value, ['Delivered', 'Cancelled'], true)
            && !$isProviderOrderCreated;

        $showConfirmShipButton =
            $order->order_status?->value === OrderStatus::PENDING->value
            && $isApiProviderOrder;

        return view('shop.order.show', compact(
            'order',
            'orderStatus',
            'riders',
            'isManualDelivery',
            'showRetryShipButton',
            'showCreateShipmentButton',
            'showConfirmShipButton',
            'retryShipProvider',
            'apiProviderStatus',
            'apiProviderStatusLabel',
            'apiProviderStatusClass',
            'apiProviderFailureReason'
        ));
    }

    /**
     * Update the order status.
     */
    public function statusChange(Order $order, Request $request)
    {
        $request->validate(['status' => 'required']);

        if (
            $request->status === OrderStatus::READY_TO_PAYMENT->value
            && $order->payment_method === PaymentMethod::CASH
        ) {
            return back()->with('error', __('Ready to Payment is allowed only for online payment orders.'));
        }

        if (
            $request->status === OrderStatus::READY_TO_PAYMENT->value
            && $order->order_status?->value !== OrderStatus::PENDING->value
        ) {
            return back()->with('error', __('Ready to Payment can be set only when order is pending.'));
        }

        if (
            $request->status === OrderStatus::PAYMENT_SUCCESSFUL->value
        ) {
            return back()->with('error', __('Payment Successful is updated automatically after payment.'));
        }

        if ($request->status == OrderStatus::CANCELLED->value) {
            $payment = $order->payments()->latest('payments.id')->first();

            $isOnlineOrder = $order->payment_method !== PaymentMethod::CASH;
            $isPaid = (bool) ($payment?->is_paid ?? false);
            $hasRazorpayOrderId = ! empty($payment?->razorpay_order_id);
            $isPaidOnline = $isOnlineOrder && $isPaid && $hasRazorpayOrderId;

            if ($isPaidOnline) {
                $refund = $this->refundRazorpayPaymentForOrder($order, $payment);
                if (! $refund['status']) {
                    return back()->with('error', __('Unable to cancel order because refund failed. ') . $refund['message']);
                }
            }
        }

        $order->update(['order_status' => $request->status]);

        $orderProvider = strtolower(trim((string) ($order->api_provider ?: $order->shop?->deliverySetting?->delivery_provider ?: '')));
        $providerShipError = null;

        if ($request->status === OrderStatus::CONFIRM->value && in_array($orderProvider, ['shiprocket', 'delhivery'], true)) {
            if ($order->api_provider !== $orderProvider) {
                $order->update(['api_provider' => $orderProvider]);
            }

            if ($orderProvider === 'shiprocket' && empty($order->provider_order_id) && empty($order->shiprocket_order_id)) {
                try {
                    $service = app(ShiprocketOrderSyncService::class);
                    $synced = $service->sync($order);
                    if (!$synced) {
                        $providerShipError = $service->getLastSyncError() ?: 'Shiprocket shipping failed.';
                    }
                    $order->refresh();
                } catch (\Throwable $e) {
                    Log::warning('Shiprocket sync failed on seller order accept (shop panel)', [
                        'order_id' => $order->id,
                        'message' => $e->getMessage(),
                    ]);
                    $providerShipError = $e->getMessage();
                }
            }

            if ($orderProvider === 'delhivery') {
                try {
                    if (empty($order->provider_order_id)) {
                        $service = app(DelhiveryOrderSyncService::class);
                        $synced = $service->sync($order);
                        if (!$synced) {
                            $providerShipError = $service->getLastSyncError() ?: 'Delhivery shipping failed.';
                        }
                        $order->refresh();
                    }
                } catch (\Throwable $e) {
                    Log::warning('Delhivery order create failed on seller order accept (shop panel)', [
                        'order_id' => $order->id,
                        'message' => $e->getMessage(),
                    ]);
                    $providerShipError = $e->getMessage();
                }
            }
        }

        // if ($request->status === OrderStatus::SHIPPED->value && empty($order->shiprocket_awb_code)) {
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
        //         Log::warning('Shiprocket pickup request failed on seller order shipped (shop panel)', [
        //             'order_id' => $order->id,
        //             'message' => $e->getMessage(),
        //         ]);
        //     }
        // }

        OrderStatusTimeline::updateOrCreate(
            [
                'order_id' => $order->id,
                'status' => $request->status,
            ],
            [
                'changed_at' => Carbon::now(),
            ]
        );

        if ($request->status == OrderStatus::DELIVERED->value) {
            $this->updateWalletAndTransaction($order);
        }

        if ($request->status == OrderStatus::CANCELLED->value) {
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
        }

        $title = 'Order status ' . $request->status;
        $message = 'Your order ' . $request->status . ' order id: #' . $order->order_code;
        $deviceKeys = $order->customer->user->devices->pluck('key')->toArray();

        $noty = null;
        try {
            $noty =  NotificationServices::sendNotification($message, $deviceKeys, $title);
        } catch (\Throwable $th) {
        }

        if (!empty($providerShipError)) {
            return back()
                ->with('error', 'Order status updated to Confirm, but shipping failed. Reason: ' . $providerShipError)
                ->with('provider_ship_error', $providerShipError);
        }

        return back()->with('success', __('Order status updated successfully.'));
    }

    public function retryShip(Order $order)
    {
        $order->loadMissing(['shop.deliverySetting']);

        if ($order->order_status?->value !== OrderStatus::CONFIRM->value) {
            return back()->with('error', __('Retry shipping is allowed only for confirmed orders.'));
        }

        $orderProvider = strtolower(trim((string) ($order->api_provider ?: $order->shop?->deliverySetting?->delivery_provider ?: '')));

        if (!in_array($orderProvider, ['shiprocket', 'delhivery'], true)) {
            return back()->with('error', __('No supported API provider found for this order.'));
        }

        if ($order->api_provider !== $orderProvider) {
            $order->update(['api_provider' => $orderProvider]);
        }

        try {
            $synced = false;
            $providerFailureReason = null;

            if ($orderProvider === 'shiprocket') {
                $service = app(ShiprocketOrderSyncService::class);
                $synced = $service->sync($order);
                $providerFailureReason = $service->getLastSyncError();
            }

            if ($orderProvider === 'delhivery') {
                $service = app(DelhiveryOrderSyncService::class);
                $synced = $service->sync($order);
                $providerFailureReason = $service->getLastSyncError();
            }

            $order->refresh();

            if (!$synced) {
                $reason = $providerFailureReason ?? null;
                $message = 'Retry ship failed. Please check provider credentials or shipment data and try again.';
                if (!empty($reason)) {
                    $message .= ' Reason: ' . $reason;
                }

                return back()->with('error', $message)->with('provider_ship_error', $reason);
            }

            return back()->with('success', __('Order has been shipped successfully via :provider.', ['provider' => ucfirst($orderProvider)]));
        } catch (\Throwable $e) {
            Log::warning('Order retry ship failed (shop panel)', [
                'order_id' => $order->id,
                'provider' => $orderProvider,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', __('Retry ship failed due to an exception. Please try again.'));
        }
    }

    public function createShipment(Order $order)
    {
        $order->loadMissing(['shop.deliverySetting']);

        if (in_array($order->order_status?->value, [OrderStatus::DELIVERED->value, OrderStatus::CANCELLED->value], true)) {
            return back()->with('error', __('Shipment cannot be created for delivered or cancelled orders.'));
        }

        $orderProvider = strtolower(trim((string) ($order->api_provider ?: $order->shop?->deliverySetting?->delivery_provider ?: '')));

        if (!in_array($orderProvider, ['shiprocket', 'delhivery'], true)) {
            return back()->with('error', __('No supported API provider found for this order.'));
        }

        $hasProviderShipmentId = !empty($order->provider_shipment_id) || !empty($order->shiprocket_shipment_id);
        $hasProviderAwb = !empty($order->provider_awb_code) || !empty($order->shiprocket_awb_code);

        if ($hasProviderShipmentId || $hasProviderAwb) {
            return back()->with('success', __('Shipment already exists for this order.'));
        }

        if ($order->api_provider !== $orderProvider) {
            $order->update(['api_provider' => $orderProvider]);
        }

        try {
            $synced = false;
            $providerFailureReason = null;

            if ($orderProvider === 'shiprocket') {
                $service = app(ShiprocketOrderSyncService::class);
                $synced = $service->sync($order);
                $providerFailureReason = $service->getLastSyncError();
            }

            if ($orderProvider === 'delhivery') {
                $service = app(DelhiveryOrderSyncService::class);
                $synced = $service->sync($order);
                $providerFailureReason = $service->getLastSyncError();
            }

            $order->refresh();

            if (!$synced) {
                $reason = $providerFailureReason ?? null;
                $message = 'Create shipment failed. Please check provider credentials or shipment data and try again.';
                if (!empty($reason)) {
                    $message .= ' Reason: ' . $reason;
                }

                return back()->with('error', $message)->with('provider_ship_error', $reason);
            }

            return back()->with('success', __('Shipment has been created successfully via :provider.', ['provider' => ucfirst($orderProvider)]));
        } catch (\Throwable $e) {
            Log::warning('Order create shipment failed (shop panel)', [
                'order_id' => $order->id,
                'provider' => $orderProvider,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', __('Create shipment failed due to an exception. Please try again.'));
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
                Log::warning('Razorpay refund failed on seller cancel', [
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
            Log::warning('Razorpay refund exception on seller cancel', [
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

    /**
     * Update track URL and delivery charge for manual delivery.
     */
    public function updateTrackingAndCharge(Order $order, Request $request)
    {
        if (in_array($order->order_status->value, [OrderStatus::DELIVERED->value, OrderStatus::CANCELLED->value])) {
            return back()->with('error', __('Tracking URL and delivery charge cannot be updated for delivered or cancelled orders.'));
        }

        $request->validate([
            'track_url' => 'nullable|url|max:500',
            'delivery_charge' => 'nullable|numeric|min:0',
        ]);

        $updateData = [];

        if ($request->filled('track_url')) {
            $updateData['track_url'] = $request->track_url;
        }

        // Only allow updating delivery_charge if manual delivery or current delivery_charge == 0
        $deliverySetting = $order->shop->deliverySetting;
        $isManualDelivery = $deliverySetting && $deliverySetting->delivery_mode === 'manual';
        $isReadyToPaymentFlow = $request->input('source') === 'ready_to_payment';
        $canUpdateViaReadyToPaymentFlow =
            $isReadyToPaymentFlow
            && $order->order_status?->value === OrderStatus::PENDING->value;

        if ($request->filled('delivery_charge')) {
            if ($isManualDelivery || $order->delivery_charge == 0 || $canUpdateViaReadyToPaymentFlow) {
                $oldDeliveryCharge = $order->delivery_charge;
                $newDeliveryCharge = $request->delivery_charge;
                $updateData['delivery_charge'] = $newDeliveryCharge;
                // Recalculate payable amount
                $updateData['payable_amount'] = $order->payable_amount - $oldDeliveryCharge + $newDeliveryCharge;
            }
        }

        if (!empty($updateData)) {
            $order->update($updateData);
            return back()->with('success', __('Order tracking and charges updated successfully.'));
        }

        return back()->with('info', __('No changes made.'));
    }

    /**
     * Update the payment status.
     */
    public function paymentStatusToggle(Order $order)
    {
        if ($order->payment_status->value == PaymentStatus::PAID->value) {
            return back()->with('error', __('When order is paid, payment status cannot be changed.'));
        }
        $order->update(['payment_status' => PaymentStatus::PAID->value]);

        return back()->with('success', __('Payment status updated successfully'));
    }

    public function downloadInvoice($id)
    {
        $order = Order::findOrFail($id);

        $orderCode = '#' . $order->prefix . $order->order_code;

        $qrCode = new EndroidQrCode($orderCode);
        $qrCode->setSize(100);

        $writer = new PngWriter;
        $qrCodeImage = $writer->write($qrCode)->getDataUri();

        // pdf config
        $defaultConfig = (new ConfigVariables)->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables)->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $fontData['kalpurush'] = [
            'R' => 'kalpurush.ttf',
        ];

        $paperSize = 'A4';

        $mPdf = new Mpdf([
            'mode' => 'UTF-8',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => storage_path('app/public/mpdf_tmp'),
            'fontDir' => array_merge($fontDirs, [public_path('fonts')]),
            'fontdata' => $fontData,
            'format' => $paperSize,
        ]);

        $view = view('PDF.invoice', compact('order', 'qrCodeImage'))->render();
        $mPdf->WriteHTML($view);

        // Output the PDF as a download
        return $mPdf->Output('invoice-' . $order->prefix . $order->order_code . '.pdf', 'D');

        // Output the PDF as a stream
        // return $mPdf->Output('invoice-' . $order->prefix . $order->order_code . '.pdf', 'I');
    }

    public function paymentSlip($id)
    {
        $order = Order::findOrFail($id);

        // pdf config
        $defaultConfig = (new ConfigVariables)->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables)->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $fontData['kalpurush'] = [
            'R' => 'kalpurush.ttf',
        ];

        $paperSize = 'A4';

        $mPdf = new Mpdf([
            'mode' => 'UTF-8',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => storage_path('app/public/mpdf_tmp'),
            'fontDir' => array_merge($fontDirs, [public_path('fonts')]),
            'fontdata' => $fontData,
            'format' => $paperSize,
        ]);

        $view = view('PDF.payment-slip', compact('order'))->render();
        $mPdf->WriteHTML($view);

        $pdfContent = $mPdf->Output('payment-slip-' . $order->prefix . $order->order_code . '.pdf', 'S');

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="payment-slip-' . $order->prefix . $order->order_code . '.pdf"',
        ]);
    }

    private function updateWalletAndTransaction($order)
    {

        $generaleSetting = generaleSetting('setting');

        $commission = 0;

        if ($generaleSetting?->business_based_on == 'commission' && $generaleSetting?->commission_charge != 'monthly') {

            if ($generaleSetting?->commission_type != 'fixed') {
                $commission = $order->total_amount * $generaleSetting->commission / 100;
            } else {
                $commission = $generaleSetting->commission ?? 0;
            }
        }

        $order->update([
            'delivery_date' => now(),
            'delivered_at' => now(),
            'payment_status' => PaymentStatus::PAID->value,
            'admin_commission' => $commission,
        ]);

        if ($generaleSetting?->business_based_on == 'commission' && $generaleSetting?->commission_charge != 'monthly') {
            $wallet = $order->shop->user->wallet;

            WalletRepository::updateByRequest($wallet, $order->payable_amount, 'credit');

            TransactionRepository::storeByRequest($wallet, $commission, 'debit', true, true, 'admin commission', 'order', $order->id, null);
        }
    }
}

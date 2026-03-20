@extends('layouts.app')
@section('header-title', __('Order Details'))

@section('content')

    <div class="row my-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between gap-2 py-3">
                    <h4 class="card-title mb-0">{{ __('Order Details') }}</h4>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('shop.payment-slip', $order->id) }}" target="_blank" class="btn btn-success py-2.5">
                            <img src="{{ asset('assets/icons-admin/download-alt.svg') }}" alt="icon" loading="lazy"
                                width="20" />
                            {{ __('Payment Slip') }}
                        </a>
                        <a href="{{ route('shop.download-invoice', $order->id) }}" target="_blank"
                            class="btn btn-primary py-2.5">
                            <img src="{{ asset('assets/icons-admin/download-alt.svg') }}" alt="icon" loading="lazy"
                                width="20" />
                            {{ __('Download Invoice') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-3 flex-wrap align-items-center">
                        <div class="flex-grow-1">
                            <div class="order-item">
                                <label class="label">{{ __('Order Id') }}:</label>
                                <span class="value">#{{ $order->prefix . $order->order_code }}</span>
                            </div>
                            <div class="order-item">
                                <label class="label">{{ __('Shop Name') }}:</label>
                                <span class="value">{{ $order->shop?->name }}</span>
                            </div>
                            <!-- <div class="order-item">
                                <label class="label">{{ __('Payment Status') }}:</label>
                                <span class="value">{{ $order->payment_status }}</span>
                            </div> -->
                            <div class="order-item">
                                <label class="label">{{ __('Payment Method') }}:</label>
                                @php
                                    $paymentMethodValue = (string) ($order->payment_method?->value ?? $order->payment_method);
                                    $normalizedPaymentMethod = strtolower(trim($paymentMethodValue));
                                @endphp
                                <span class="value">{{ $normalizedPaymentMethod === 'cash payment' ? 'Cash on Delivery' : $paymentMethodValue }}</span>
                            </div>
                            @php
                                $latestPayment = $order->payments()->latest('payments.id')->first();
                                $isOnlinePayment = !in_array($normalizedPaymentMethod, ['cash', 'cash payment'], true);

                                $gatewayProviderKey = strtolower((string) ($latestPayment?->payment_method ?? ''));
                                if (!in_array($gatewayProviderKey, ['razorpay', 'cashfree'], true)) {
                                    $shopProvider = strtolower((string) ($order->shop?->online_payment_provider ?? ''));
                                    if (in_array($shopProvider, ['razorpay', 'cashfree'], true)) {
                                        $gatewayProviderKey = $shopProvider;
                                    }
                                }
                                if (!in_array($gatewayProviderKey, ['razorpay', 'cashfree'], true)) {
                                    if (in_array($normalizedPaymentMethod, ['razorpay', 'cashfree'], true)) {
                                        $gatewayProviderKey = $normalizedPaymentMethod;
                                    }
                                }

                                $gatewayProviderLabel = null;
                                if ($gatewayProviderKey === 'razorpay') {
                                    $gatewayProviderLabel = 'Razorpay';
                                } elseif ($gatewayProviderKey === 'cashfree') {
                                    $gatewayProviderLabel = 'Cashfree';
                                }

                                $showGatewayDetails = $isOnlinePayment && !empty($gatewayProviderLabel);

                                $gatewayPaymentStatus = null;
                                $providerOrderId = null;
                                $providerPaymentId = null;
                                if ($showGatewayDetails) {
                                    $normalizedOrderPaymentStatus = strtolower((string) ($order->payment_status?->value ?? $order->payment_status));

                                    if ($normalizedOrderPaymentStatus === 'paid') {
                                        $gatewayPaymentStatus = 'Paid';
                                    } elseif (!$latestPayment) {
                                        $gatewayPaymentStatus = 'Pending';
                                    } elseif ($gatewayProviderKey === 'razorpay') {
                                        if (!empty($latestPayment->razorpay_refund_id)) {
                                            $gatewayPaymentStatus = 'Refunded';
                                        } elseif (!empty($latestPayment->is_paid)) {
                                            $gatewayPaymentStatus = 'Paid';
                                        } elseif (!empty($latestPayment->razorpay_payment_id)) {
                                            $gatewayPaymentStatus = 'Authorized';
                                        } elseif (!empty($latestPayment->razorpay_order_id)) {
                                            $gatewayPaymentStatus = 'Created';
                                        } else {
                                            $gatewayPaymentStatus = 'Pending';
                                        }
                                    } elseif ($gatewayProviderKey === 'cashfree') {
                                        if (!empty($latestPayment->is_paid)) {
                                            $gatewayPaymentStatus = 'Paid';
                                        } elseif (!empty($latestPayment->payment_token)) {
                                            $gatewayPaymentStatus = 'Created';
                                        } else {
                                            $gatewayPaymentStatus = 'Pending';
                                        }
                                    }

                                    if ($gatewayProviderKey === 'razorpay') {
                                        $providerOrderId = $latestPayment?->razorpay_order_id;
                                        $providerPaymentId = $latestPayment?->razorpay_payment_id;
                                    } elseif ($gatewayProviderKey === 'cashfree') {
                                        $providerOrderId = $latestPayment?->payment_token;
                                        $providerPaymentId = null;
                                    }
                                }
                            @endphp
                            @if ($showGatewayDetails)
                                <div class="order-item">
                                    <label class="label">Payment Provider:</label>
                                    <span class="value">{{ $gatewayProviderLabel }}</span>
                                </div>
                                @if (!empty($providerOrderId))
                                    <div class="order-item">
                                        <label class="label">Provider Order ID:</label>
                                        <span class="value">{{ $providerOrderId }}</span>
                                    </div>
                                @endif
                                @if (!empty($providerPaymentId))
                                    <div class="order-item">
                                        <label class="label">Provider Payment ID:</label>
                                        <span class="value">{{ $providerPaymentId }}</span>
                                    </div>
                                @endif
                                <div class="order-item">
                                    <label class="label">Payment Current Status:</label>
                                    <span class="value">{{ $gatewayPaymentStatus }}</span>
                                </div>
                            @endif
                            <div class="order-item">
                                <label class="label">{{ __('GST') }}:</label>
                                <span class="value">{{ $order->gst ?? '_' }}</span>
                            </div> 
                        </div>

                        <div class="item-divider"></div>

                        <div class="flex-grow-1">
                            <div class="order-item">
                                <label class="label">{{ __('Order Status') }}:</label>
                                <span class="value">{{ $order->order_status }}</span>
                            </div>
                            <div class="order-item">
                                <label class="label">{{ __('Order Date') }}:</label>
                                <span class="value">{{ $order->created_at->format('M d, Y | h:i A') }}</span>
                            </div>
                            <!-- <div class="order-item">
                                <label class="label">{{ __('Delivery Date') }}:</label>
                                <span class="value">
                                    {{ $order->delivery_date ? Carbon\Carbon::parse($order->delivery_date)->format('M d, Y') : '-' }}
                                </span>
                            </div> -->
                        </div>
                    </div>

                    <div class="table-responsive mt-4 mb-0">
                        <table class="table border-left-right">
                            <thead>
                                <tr>
                                    <th>{{ __('SL') }}</th>
                                    <th>{{ __('Product') }}</th>
                                    <!-- @if ($businessModel == 'multi')
                                        <th>{{ __('Shop') }}</th>
                                    @endif -->
                                    <th>{{ __('Quantity') }}</th>
                                    <th>{{ __('Size') }}</th>
                                    <th>{{ __('Color') }}</th>
                                    <th>{{ __('Price') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->orderProducts as $key => $product)
                                    @php
                                        // $this->load('brand', 'reviews');
                                        $pname = $product->name;
                                        $dprice = 0;
                                        $mprice = (float) number_format((float) $product->price, 2, '.', '');

                                        $color = null;
                                        $size = null;

                                        if ($product->orderVariant != null) {
                                            $dprice = (float) number_format($product->orderVariant->price, 2, '.', '');
                                            $color = $product->orderVariant->color_name;
                                            $size = $product->orderVariant->size_name;
                                        } elseif ($product->orderBulkItem != null) {
                                            $pname = $product->orderBulkItem->name;
                                            $mprice = (float) number_format($product->orderBulkItem->mrp, 2, '.', '');
                                            $dprice = (float) number_format($product->orderBulkItem->selling_price, 2);
                                        } else {
                                            $dprice = (float) number_format($product->price, 2, '.', '');
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>
                                            <div class="d-flex gap-1 align-items-center">
                                                <img src="{{ $product->product->thumbnail }}" alt="" width="40"
                                                    height="40" loading="lazy">
                                                <span>{{ $pname }}</span>
                                            </div>
                                        </td>
                                        <!-- @if ($businessModel == 'multi')
                                            <td>{{ $product->shop?->name }}</td>
                                        @endif -->
                                        <td>{{ $product->quantity }}</td>
                                        <td>{{ $product->orderVariant?->size_name ?? '-' }}</td>
                                        <td>{{ $product->orderVariant?->color_name ?? '-' }}</td>
                                        <td>
                                            {{ showCurrency($dprice) }}
                                        </td>
                                        <td class="text-end">
                                            {{ showCurrency($product->quantity * $dprice) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="max-300 ms-auto d-flex flex-column gap-1">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <div>{{ __('Sub Total') }}</div>
                            <div>{{ showCurrency($order->total_amount) }}</div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <div>{{ __('Coupon Discount') }}</div>
                            <div>{{ showCurrency($order->coupon_discount) }}</div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <div>{{ __('Delivery Charge') }}</div>
                            <div>{{ showCurrency($order->delivery_charge) }}</div>
                        </div>

                        <!-- <div class="d-flex align-items-center justify-content-between gap-2">
                            <div>{{ __('VAT & Tax') }}</div>
                            <div>{{ showCurrency($order->tax_amount) }}</div>
                        </div> -->

                        <div class="d-flex align-items-center justify-content-between gap-2 border-top pt-1 mt-1">
                            <div class="fw-bold">{{ __('Grand Total') }}</div>
                            <div class="fw-bold">{{ showCurrency($order->payable_amount) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!--##### Customer Info #####-->
            <div class="mt-3 card">
                <h5 class="fz-16 border-bottom px-3 py-12 m-0">{{ __('Customer Info') }}</h5>

                <div class="border-bottom px-3 py-2 d-flex  align-items-center gap-3">
                    <span class="text-color">{{ __('Name') }}: </span>
                    <span class="fw-medium">{{ $order->customer?->user?->name }}</span>
                </div>
                <div class="px-3 py-2 d-flex  align-items-center gap-3">
                    <span class="text-color">{{ __('Phone') }}: </span>
                    <span class="fw-medium">{{ $order->customer?->user?->phone }}</span>
                </div>
            </div>

        </div>

        <div class="col-lg-4">
            <!--##### Order & Shipping Info #####-->
            <div class="card">
                <h5 class="fz-18 border-bottom p-3 m-0">{{ __('Order & Shipping Info') }}</h5>

                @php
                    $isCashOrderForStatusAction = in_array($normalizedPaymentMethod ?? '', ['cash', 'cash payment'], true);
                    $isDeliveryApiEnabled = (bool) ($order->shop?->deliverySetting?->delivery_api_enabled ?? false);
                    $isAdminContext = request()->routeIs('admin.*');
                    $statusChangePermission = $isAdminContext ? ['admin.order.status.change'] : ['shop.order.status.change'];
                    $statusChangeRouteName = $isAdminContext ? 'admin.order.status.change' : 'shop.order.status.change';
                    $showReadyToPaymentButton =
                        ($order->order_status->value === 'Pending')
                        && !$isCashOrderForStatusAction;
                @endphp

                <div class="px-3 py-12 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                    <div class="text-color">{{ __('Change Order Status') }}</div>
                    @php
                        // Show buttons based on order status and payment
                        $isOnlinePayment = !in_array($normalizedPaymentMethod, ['cash', 'cash payment'], true);
                        $isPaid = (bool) (($order->payments()->latest('payments.id')->first()?->is_paid) ?? false);
                        $isPending = $order->order_status->value === 'Pending';
                        $isPaymentSuccessful = $order->order_status->value === 'Payment Successful';
                        $isConfirm = $order->order_status->value === 'Confirm';
                        
                        // Show Cancel/Confirm buttons only when Pending
                        $showCancelConfirmButtons = $isPending && ($isOnlinePayment && $isPaid || !$isOnlinePayment);
                    @endphp
                    
                    @if ($isAdminContext)
                        <div class="dropdown">
                            <a class="btn border text-start dropdown-toggle" href="#" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                {{ $order->order_status->value }}
                            </a>
                            @hasPermission($statusChangePermission)
                                <ul class="dropdown-menu order-status">
                                    @foreach ($orderStatus as $status)
                                        @php
                                            $isShippedStatus = $status->value === 'Shipped';
                                        @endphp
                                        <li>
                                            <a class="dropdown-item @if (in_array($status->value, ['Delivered', 'Cancelled', 'Shipped'], true)) OrderStatusConfirm @endif @if ($isShippedStatus) js-status-shipped @endif"
                                                href="{{ route($statusChangeRouteName, $order->id) }}?status={{ $status->value }}"
                                                @if ($isShippedStatus)
                                                    data-update-url="{{ route('shop.order.update-tracking', $order->id) }}"
                                                    data-token="{{ csrf_token() }}"
                                                @endif>
                                                {{ __($status->value) }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endhasPermission
                        </div>
                    @elseif (!$isDeliveryApiEnabled)
                        @php
                            $currentStatusValue = (string) $order->order_status->value;
                            $manualNextStatuses = [];

                            if ($currentStatusValue === 'Pending') {
                                $manualNextStatuses = ['Confirm', 'Cancelled'];
                            } elseif ($currentStatusValue === 'Payment Successful') {
                                $manualNextStatuses = ['Confirm'];
                            } elseif ($currentStatusValue === 'Confirm') {
                                $manualNextStatuses = ['Shipped'];
                            } elseif ($currentStatusValue === 'Shipped') {
                                $manualNextStatuses = ['Delivered'];
                            }

                            $canManuallyChangeStatus = !empty($manualNextStatuses);
                        @endphp

                        @if ($canManuallyChangeStatus)
                            <div class="dropdown">
                                <a class="btn border text-start dropdown-toggle" href="#" role="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    {{ $order->order_status->value }}
                                </a>
                                @hasPermission($statusChangePermission)
                                    <ul class="dropdown-menu order-status">
                                        @foreach ($manualNextStatuses as $nextStatus)
                                            @php
                                                $isShippedNextStatus = $nextStatus === 'Shipped';
                                            @endphp
                                            <li>
                                                <a class="dropdown-item @if (in_array($nextStatus, ['Delivered', 'Cancelled', 'Shipped'], true)) OrderStatusConfirm @endif @if ($isShippedNextStatus) js-status-shipped @endif"
                                                    href="{{ route($statusChangeRouteName, $order->id) }}?status={{ $nextStatus }}"
                                                    @if ($isShippedNextStatus)
                                                        data-update-url="{{ route('shop.order.update-tracking', $order->id) }}"
                                                        data-token="{{ csrf_token() }}"
                                                    @endif>
                                                    {{ __($nextStatus) }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endhasPermission
                            </div>
                        @else
                            <div class="text-muted small">
                                {{ $order->order_status->value }}
                            </div>
                        @endif
                    @elseif ($showCancelConfirmButtons)
                        <!-- Pending status: Show Cancel and Confirm buttons side by side -->
                        <div class="d-flex gap-2">
                            <a href="{{ route('shop.order.status.change', $order->id) }}?status=Cancelled" 
                               class="btn btn-danger btn-sm OrderStatusConfirm">
                                {{ __('Cancel') }}
                            </a>
                            <a href="{{ route('shop.order.status.change', $order->id) }}?status=Confirm" 
                               class="btn btn-success btn-sm OrderStatusConfirm">
                                {{ __('Confirm') }}
                            </a>
                        </div>
                    @elseif ($isPaymentSuccessful)
                        <div class="d-flex gap-2">
                            <a href="{{ route('shop.order.status.change', $order->id) }}?status=Confirm"
                               class="btn btn-success btn-sm OrderStatusConfirm">
                                {{ __('Confirm') }}
                            </a>
                        </div>
                    @elseif ($isConfirm)
                        <!-- Confirm status: Show status only, Create Shipment is below -->
                        <div class="text-muted small">
                            {{ $order->order_status->value }}
                        </div>
                    @else
                        <!-- Other statuses: Show dropdown -->
                        <div class="dropdown">
                            <a class="btn border text-start dropdown-toggle" href="#" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                {{ $order->order_status->value }}
                            </a>
                            @if ($order->order_status->value != 'Delivered' && $order->order_status->value != 'Cancelled')
                                @hasPermission($statusChangePermission)
                                    <ul class="dropdown-menu order-status">
                                        @foreach ($orderStatus as $status)
                                            @php
                                                $isCashOrder = in_array($normalizedPaymentMethod, ['cash', 'cash payment'], true);
                                                $isOnlineOnlyStatus = in_array($status->value, ['Ready to Payment', 'Payment Successful'], true);
                                                $isSystemPaymentStatus = $status->value === 'Payment Successful';
                                                $isShippedStatus = $status->value === 'Shipped';
                                            @endphp
                                            @if (!($isCashOrder && $isOnlineOnlyStatus) && !$isSystemPaymentStatus)
                                            <li>
                                                <a class="dropdown-item @if (in_array($status->value, ['Delivered', 'Cancelled', 'Shipped'])) OrderStatusConfirm @endif @if ($isShippedStatus) js-status-shipped @endif"
                                                    href="{{ route($statusChangeRouteName, $order->id) }}?status={{ $status->value }}"
                                                    @if ($isShippedStatus)
                                                        data-update-url="{{ route('shop.order.update-tracking', $order->id) }}"
                                                        data-token="{{ csrf_token() }}"
                                                    @endif>
                                                    {{ __($status->value) }}
                                                </a>
                                            </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                @endhasPermission
                            @endif
                        </div>
                    @endif
                </div>

                @if ($isDeliveryApiEnabled && $showCreateShipmentButton && $order->order_status->value === 'Confirm')
                    @hasPermission(['shop.order.status.change'])
                        <div class="p-3 border-bottom">
                            <form action="{{ route('shop.order.create-shipment', $order->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    {{ __('Create Shipment') }}
                                </button>
                            </form>
                        </div>
                    @endhasPermission
                @endif

                @php
                    // Ready to Payment: show only when Pending, Online Payment, and NOT Paid
                    $showReadyToPaymentButtonNew = 
                        ($order->order_status->value === 'Pending') &&
                        (!in_array($normalizedPaymentMethod, ['cash', 'cash payment'], true)) &&
                        (!(($order->payments()->latest('payments.id')->first()?->is_paid) ?? false));
                @endphp

                @if ($showReadyToPaymentButtonNew)
                    @hasPermission(['shop.order.status.change'])
                        <div class="p-3 border-bottom">
                            <button
                                type="button"
                                class="btn btn-warning btn-sm w-100 js-ready-to-payment"
                                data-update-url="{{ route('shop.order.update-tracking', $order->id) }}"
                                data-status-url="{{ route('shop.order.status.change', $order->id) }}?status={{ urlencode('Ready to Payment') }}"
                                data-create-shipment-url=""
                                data-current-charge="{{ (float) ($order->delivery_charge ?? 0) }}"
                                data-token="{{ csrf_token() }}"
                            >
                                {{ __('Ready to Payment') }}
                            </button>
                        </div>
                    @endhasPermission
                @endif

                @if ($showCreateShipmentButton && $showReadyToPaymentButtonNew)
                    @hasPermission(['shop.order.status.change'])
                        <div class="py-1 text-center">
                            <small class="text-muted">Complete payment first to proceed with shipment.</small>
                        </div>
                    @endhasPermission
                @endif

                <!-- Shipment Details Section -->
                @php
                    $shipmentProvider = strtolower(trim((string) ($order->api_provider ?: $retryShipProvider ?: $order->shop?->deliverySetting?->delivery_provider ?: '')));
                    if (empty($shipmentProvider) && (!empty($order->shiprocket_shipment_id) || !empty($order->shiprocket_order_id))) {
                        $shipmentProvider = 'shiprocket';
                    }
                    $shipmentOrderId = $order->provider_order_id ?: $order->shiprocket_order_id;
                    $shipmentAwb = $order->provider_awb_code ?: $order->shiprocket_awb_code;
                    $shipmentTrackUrl = $order->track_url;
                    if (empty($shipmentTrackUrl) && !empty($shipmentAwb) && $shipmentProvider === 'shiprocket') {
                        $shipmentTrackUrl = 'https://shiprocket.co/tracking/' . $shipmentAwb;
                    }
                    $hasShipmentDetails =
                        !empty($shipmentOrderId)
                        || !empty($order->provider_shipment_id)
                        || !empty($order->shiprocket_shipment_id)
                        || !empty($shipmentAwb)
                        || !empty($shipmentTrackUrl)
                        || !empty($shipmentProvider);
                @endphp
                @if ($isDeliveryApiEnabled && $hasShipmentDetails)
                    <div class="p-3 border-bottom">
                        <h6 class="fz-14 mb-3">{{ __('Shipment Details') }}</h6>
                        @if (!empty($shipmentProvider))
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <span class="text-color">{{ __('Delivery Provider') }}:</span>
                                <span class="fw-medium">{{ ucfirst($shipmentProvider) }}</span>
                            </div>
                        @endif
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <span class="text-color">{{ __('Order Code') }}:</span>
                            <span class="fw-medium">{{ $order->prefix . $order->order_code }}</span>
                        </div>
                        @if (!empty($order->provider_current_status) || !empty($apiProviderStatusLabel))
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <span class="text-color">{{ __('Provider Status') }}:</span>
                                <span class="fw-medium">
                                    @if (!empty($order->provider_current_status))
                                        <span class="badge bg-{{ $apiProviderStatusClass }}">{{ strtoupper($order->provider_current_status) }}</span>
                                    @else
                                        <span class="badge bg-{{ $apiProviderStatusClass }}">{{ __($apiProviderStatusLabel) }}</span>
                                    @endif
                                </span>
                            </div>
                        @endif
                        @if (!empty($order->provider_shipment_id))
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <span class="text-color">{{ __('Shipment ID') }}:</span>
                                <span class="fw-medium">{{ $order->provider_shipment_id }}</span>
                            </div>
                        @endif
                        @if (!empty($shipmentAwb))
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <span class="text-color">{{ __('AWB Code') }}:</span>
                                <span class="fw-medium">{{ $shipmentAwb }}</span>
                            </div>
                        @endif
                        @if (!empty($shipmentTrackUrl))
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <span class="text-color">{{ __('Tracking URL') }}:</span>
                                <a href="{{ $shipmentTrackUrl }}" target="_blank" class="fw-medium text-primary small">
                                    {{ __('View Tracking') }} →
                                </a>
                            </div>
                        @endif
                        @if (!empty($order->pickup_date))
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <span class="text-color">{{ __('Pickup Date') }}:</span>
                                <span class="fw-medium">{{ $order->pickup_date ? \Carbon\Carbon::parse($order->pickup_date)->format('M d, Y | h:i A') : '-' }}</span>
                            </div>
                        @endif
                        @if (!empty($order->delivery_date))
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <span class="text-color">{{ __('Delivery Date') }}:</span>
                                <span class="fw-medium">{{ $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('M d, Y | h:i A') : '-' }}</span>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="p-3 border-bottom">
                    <h6 class="fz-14 mb-3">{{ __('Update Tracking & Delivery Charge') }}</h6>
                    @php
                        $isTrackingUpdateLocked = in_array($order->order_status->value, ['Ready to Payment', 'Payment Successful', 'Shipped', 'Delivered', 'Cancelled']);
                        $isApiProviderEnabledForOrder = (bool) ($order->shop?->deliverySetting?->delivery_api_enabled ?? false);
                        $hasPersistedTrackUrl = !empty(trim((string) $order->track_url));
                        $isOnlinePaymentForChargeLock = !in_array(strtolower(trim((string) ($order->payment_method?->value ?? $order->payment_method))), ['cash', 'cash payment'], true);
                        $isConfirmStatusForChargeLock = (string) $order->order_status->value === 'Confirm';
                        $shouldShowTrackUrlField = !$isApiProviderEnabledForOrder || $hasPersistedTrackUrl;
                        $isTrackUrlEditable = $shouldShowTrackUrlField && !$isTrackingUpdateLocked && !$hasPersistedTrackUrl;
                        $isDeliveryChargeEditable = !$isTrackingUpdateLocked
                            && ($isManualDelivery || (float) ($order->delivery_charge ?? 0) == 0)
                            && !($isConfirmStatusForChargeLock && $isOnlinePaymentForChargeLock);
                        $showUpdateButton = $isTrackUrlEditable || $isDeliveryChargeEditable;
                    @endphp
                    @if ($showRetryShipButton)
                        @hasPermission(['shop.order.status.change'])
                            <form action="{{ route('shop.order.retry-ship', $order->id) }}" method="POST" class="mb-3">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm w-100">
                                    {{ __('Retry Ship via :provider', ['provider' => ucfirst($retryShipProvider)]) }}
                                </button>
                            </form>
                        @endhasPermission
                    @endif
                    <form action="{{ route('shop.order.update-tracking', $order->id) }}" method="POST">
                        @csrf
                        @if ($shouldShowTrackUrlField)
                            <div class="mb-3">
                                <label for="track_url" class="form-label small">{{ __('Track URL') }}</label>
                                <input type="url" class="form-control form-control-sm" id="track_url" name="track_url" 
                                    value="{{ old('track_url', $order->track_url) }}" 
                                    placeholder="Enter track url here"
                                    {{ $isTrackUrlEditable ? '' : 'disabled' }}>
                                @error('track_url')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        @endif
                                @if ($isManualDelivery || $order->delivery_charge == 0)
                                    <div class="mb-3">
                                        <label for="delivery_charge" class="form-label small">{{ __('Delivery Charge') }}</label>
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm" 
                                            id="delivery_charge" name="delivery_charge" 
                                            value="{{ old('delivery_charge', $order->delivery_charge) }}"
                                            placeholder="0.00"
                                            {{ $isDeliveryChargeEditable ? '' : 'disabled' }}>
                                        @error('delivery_charge')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                @elseif ($order->delivery_charge > 0)
                                    <div class="mb-3">
                                        <label class="form-label small">{{ __('Delivery Charge') }}</label>
                                        <div class="form-control form-control-sm bg-light">{{ $order->delivery_charge }}</div>
                                    </div>
                                @endif
                        @if ($showUpdateButton)
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                {{ __('Update') }}
                            </button>
                        @endif
                    </form>
                </div>

                <!-- <div class="border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 p-3">
                    <div class="text-color">{{ __('Payment Status') }}</div>
                    <div class="d-flex align-items-center gap-1">
                        <span>{{ $order->payment_status }}</span>
                        @hasPermission('shop.order.payment.status.toggle')
                            <label class="switch mb-0">
                                <a href="{{ route('shop.order.payment.status.toggle', $order->id) }}">
                                    <input type="checkbox" {{ $order->payment_status->value == 'Paid' ? 'checked' : '' }}>
                                    <span class="slider round"></span>
                                </a>
                            </label>
                        @endhasPermission
                    </div>
                </div> -->
            </div>

            <!--##### Shipping Address #####-->
            <div class="card mt-3">
                <h5 class="fz-18 border-bottom p-3 m-0">{{ __('Shipping Address') }}</h5>

                <div class="border-bottom d-flex align-items-center justify-content-between gap-2 px-3 py-12">
                    <span class="text-color">{{ __('Name') }}: </span>
                    <span class="fw-medium">{{ $order->address?->name }}</span>
                </div>
                <div class="border-bottom d-flex align-items-center justify-content-between gap-2 px-3 py-12">
                    <span class="text-color">{{ __('Phone') }}: </span>
                    <span class="fw-medium">{{ $order->address?->phone }}</span>
                </div>
                <div class="border-bottom d-flex align-items-center justify-content-between gap-2 px-3 py-12">
                    <span class="text-color">{{ __('Address Line') }} 1: </span>
                    <span class="fw-medium">{{ $order->address?->address_line }}</span>
                </div>
                <div class="d-flex align-items-center justify-content-between gap-2 px-3 py-12">
                    <span class="text-color">{{ __('Address Line') }} 2: </span>
                    <span class="fw-medium">{{ $order->address?->address_line2 }}</span>
                </div>
                <div class="border-bottom d-flex align-items-center justify-content-between gap-2 px-3 py-12">
                    <span class="text-color">{{ __('State & District') }}:</span>
                    <span class="fw-medium">
                        {{ $order->address?->stateData?->name }},
                        {{ $order->address?->districtData?->name }}
                    </span>
                </div>
                <div class="border-bottom d-flex align-items-center justify-content-between gap-2 px-3 py-12">
                    <span class="text-color">{{ __('PIN Code') }}: </span>
                    <span class="fw-medium">{{ $order->address?->post_code }}</span>
                </div> 
                <div class="border-bottom d-flex align-items-center justify-content-between gap-2 px-3 py-12">
                    <span class="text-color">{{ __('Address Type') }}: </span>
                    <span class="fw-medium">{{ $order->address?->address_type }}</span>
                </div> 
                <!-- <div class="border-bottom d-flex align-items-center justify-content-between gap-2 px-3 py-12">
                    <span class="text-color">{{ __('Area') }}: </span>
                    <span class="fw-medium">{{ $order->address?->area }}</span>
                </div> -->
                <!-- <div class="d-flex gap-2 border-bottom align-items-center justify-content-between flex-wrap px-3 py-12">
                    <div>
                        <span class="text-color">{{ __('Road No') }}: </span>
                        <span class="fw-medium">{{ $order->address?->road_no }}</span>,
                    </div>
                    <div>
                        <span class="text-color">{{ __('Flat No') }}: </span>
                        <span class="fw-medium">{{ $order->address?->flat_no }}</span>,
                    </div>
                    <div>
                        <span class="text-color">{{ __('House No') }}: </span>
                        <span class="fw-medium">{{ $order->address?->house_no }}</span>
                    </div>
                </div> --> 
                
            </div>

        </div>
    </div>

@endsection
@push('css')
    <style>
        .dropdown-menu.order-status {
            min-width: 200px;
            padding: 8px;
            border: 1px solid #e5e5e5;
            box-shadow: 0 0 10px #e5e5e5;
        }

        .dropdown-menu.order-status .dropdown-item {
            border-bottom: 1px solid #f1f1f1;
        }

        .app-theme-dark .dropdown-menu.order-status {
            border: 1px solid #343a40;
            box-shadow: 0 0 10px #343a40;
        }

        .app-theme-dark .dropdown-menu.order-status .dropdown-item {
            border-bottom: 1px solid #343a40;
        }

        .max-300 {
            max-width: 340px;
        }

        .min-w-200 {
            min-width: 200px;
            display: inline;
        }

        .item-divider {
            height: 80px;
            width: 1px;
            background: #e5e5e5;
            margin: 0 20px;
        }

        .app-theme-dark .item-divider {
            background: #343a40;
        }

        .order-item {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .order-item:last-child {
            margin-bottom: 0;
        }

        .order-item .label {
            color: #687387;
            line-height: 22px;
        }

        .app-theme-dark .order-item .label {
            color: #8f96a6;
        }

        .order-item .value {
            line-height: 22px;
            font-weight: 500;
            color: #000;
        }

        .app-theme-dark .order-item .value {
            color: #fff;
        }

        @media (max-width: 768px) {
            .item-divider {
                display: none;
            }
        }
    </style>
@endpush
@push('scripts')
    <script>
        $(document).ready(function() {
            const getStatusFromUrl = (url) => {
                try {
                    return new URL(url, window.location.origin).searchParams.get('status');
                } catch (error) {
                    const match = String(url || '').match(/[?&]status=([^&]+)/i);
                    return match ? decodeURIComponent(match[1]) : null;
                }
            };

            const handleShippedStatusChange = (button, statusUrl) => {
                const updateUrl = button.data('update-url') || "{{ route('shop.order.update-tracking', $order->id) }}";
                const csrfToken = button.data('token') || "{{ csrf_token() }}";

                Swal.fire({
                    title: 'Mark as Shipped',
                    text: 'Tracking URL is required before changing status to Shipped.',
                    input: 'url',
                    inputPlaceholder: 'https://example.com/track/123',
                    showCancelButton: true,
                    confirmButtonText: 'Update & Continue',
                    cancelButtonText: 'Cancel',
                    preConfirm: (value) => {
                        const trackUrl = String(value || '').trim();
                        if (!trackUrl) {
                            Swal.showValidationMessage('Track URL is required.');
                            return false;
                        }

                        try {
                            new URL(trackUrl);
                        } catch (error) {
                            Swal.showValidationMessage('Please enter a valid URL.');
                            return false;
                        }

                        return trackUrl;
                    }
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    const trackUrl = result.value;

                    Swal.fire({
                        title: 'Updating track URL...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch(updateUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: new URLSearchParams({
                            _token: csrfToken,
                            track_url: trackUrl,
                            source: 'status_shipped'
                        }).toString()
                    })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error('Failed to update tracking URL.');
                        }

                        window.location.href = statusUrl;
                    })
                    .catch((error) => {
                        Swal.fire({
                            title: 'Update Failed',
                            text: error?.message || 'Unable to update tracking URL. Please try again.',
                            icon: 'error'
                        });
                    });
                });
            };

            $(document).on('click', '.js-status-shipped, .dropdown-menu a[href*="status=Shipped"]', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                const button = $(this);
                const statusUrl = button.attr('href');
                handleShippedStatusChange(button, statusUrl);
            });

            $(".dropdown-menu").on("click", ".OrderStatusConfirm", function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                const button = $(this);
                const url = button.attr("href");
                const statusParam = String(getStatusFromUrl(url) || '').toLowerCase();
                if (statusParam === 'shipped') {
                    handleShippedStatusChange(button, url);
                    return;
                }

                const statusName = $(this).text().trim();

                Swal.fire({
                    title: "Are you sure?",
                    text: `Do you really want to mark this order as ${statusName}?`,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, proceed!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });

            $(document).on('click', '.js-ready-to-payment', function(e) {
                e.preventDefault();

                const button = $(this);
                const updateUrl = button.data('update-url');
                const statusUrl = button.data('status-url');
                const createShipmentUrl = String(button.data('create-shipment-url') || '').trim();
                const hasCreateShipmentOption = createShipmentUrl.length > 0;
                const csrfToken = button.data('token');
                const currentCharge = Number(button.data('current-charge') || 0);

                Swal.fire({
                    title: 'Ready to Payment',
                    html: hasCreateShipmentOption
                        ? 'If delivery charge is not known yet, click <b>Create Shipment First</b> to get delivery charge, then update delivery charge here.'
                        : 'Last chance to update delivery charge before switching status.',
                    input: 'number',
                    inputAttributes: {
                        min: 0,
                        step: '0.01'
                    },
                    inputValue: currentCharge,
                    showCancelButton: true,
                    showDenyButton: hasCreateShipmentOption,
                    denyButtonText: 'Create Shipment First',
                    confirmButtonText: 'Update & Continue',
                    cancelButtonText: 'Cancel',
                    preConfirm: (value) => {
                        const normalized = value === '' || value === null || typeof value === 'undefined'
                            ? currentCharge
                            : Number(value);

                        if (Number.isNaN(normalized) || normalized < 0) {
                            Swal.showValidationMessage('Please enter a valid delivery charge (0 or more).');
                            return false;
                        }

                        return normalized;
                    }
                }).then((result) => {
                    if (result.isDenied && hasCreateShipmentOption) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = createShipmentUrl;

                        const tokenInput = document.createElement('input');
                        tokenInput.type = 'hidden';
                        tokenInput.name = '_token';
                        tokenInput.value = csrfToken;
                        form.appendChild(tokenInput);

                        document.body.appendChild(form);
                        form.submit();
                        return;
                    }

                    if (!result.isConfirmed) {
                        return;
                    }

                    const deliveryCharge = result.value;

                    Swal.fire({
                        title: 'Updating delivery charge...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch(updateUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: new URLSearchParams({
                            _token: csrfToken,
                            delivery_charge: deliveryCharge,
                            source: 'ready_to_payment'
                        }).toString()
                    })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error('Failed to update delivery charge.');
                        }

                        window.location.href = statusUrl;
                    })
                    .catch((error) => {
                        Swal.fire({
                            title: 'Update Failed',
                            text: error?.message || 'Unable to update delivery charge. Please try again.',
                            icon: 'error'
                        });
                    });
                });
            });
        });
    </script>
@endpush

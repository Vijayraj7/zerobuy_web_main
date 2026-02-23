<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Roles;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\GeneraleSetting;
use App\Models\Order;
use App\Models\OrderStatusTimeline;
use App\Models\User;
use App\Repositories\NotificationRepository;
use App\Repositories\OrderRepository;
use App\Services\NotificationServices;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class OrderController extends Controller
{
    /**
     * Display a order list with filter status.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $status = $request->status;

            $orders = OrderRepository::query()
                ->with(['shop', 'customer.user', 'products'])
                ->when($status, fn($q) => $q->where('order_status', $status))
                ->latest();

            return DataTables::of($orders)
                ->addIndexColumn()
                ->addColumn('created_date', function ($order) {
                    return $order->created_at->format('d-m-Y | h:i A');
                })

                ->addColumn('order_id', function ($order) {
                    return 'ORD0' . $order->id;
                })

                ->addColumn('thumbnail', function ($order) {
                    $first = $order->products->first();
                    $thumb = $first?->thumbnail ?? ($first?->pivot?->thumbnail ?? asset('assets/images/placeholder.png'));
                    return '<img src="' . $thumb . '" alt="" width="40" height="40" style="object-fit:cover;border-radius:4px;" />';
                })

                ->addColumn('store_id', function ($order) {
                    return 'STD0' . $order->shop_id;
                })


                ->addColumn('shop_code', fn($order) => $order->shop?->shop_code)
                ->addColumn('store_name', fn($order) => $order->shop?->name)

                ->addColumn(
                    'customer_name',
                    fn($order) =>
                    $order->customer?->user?->name
                )

                ->addColumn(
                    'mobile',
                    fn($order) =>
                    $order->customer?->user?->phone ?? 'N/A'
                )

                ->addColumn('quantity', function ($order) {
                    return $order->products->sum('pivot.quantity');
                })
                ->addColumn('total_amount', function ($order) {
                    return showCurrency($order->payable_amount)
                        . '<br><span class="badge bg-primary">'
                        // . $order->payment_status->value
                        . '</span>';
                })
                ->addColumn('payment_method', function ($order) {
                    return strtoupper($order->payment_method->value);
                })
                ->addColumn('status', function ($order) {

                    $status = $order->order_status->value;

                    return match ($status) {
                        'Pending'   => '<span class="badge bg-warning">Pending</span>',
                        'Accepted'  => '<span class="badge bg-info">Accepted</span>',
                        'Shipped'   => '<span class="badge bg-primary">Shipped</span>',
                        'Delivered' => '<span class="badge bg-success">Delivered</span>',
                        'Cancelled' => '<span class="badge bg-danger">Cancelled</span>',
                        default     => '<span class="badge bg-secondary">' . $status . '</span>',
                    };
                })
                ->addColumn('action', function ($order) {
                    $viewUrl    = route('admin.order.show', $order->id);
                    $invoiceUrl = route('shop.download-invoice', $order->id);

                    $eyeIcon    = asset('assets/icons-admin/eye.svg');
                    $downIcon   = asset('assets/icons-admin/download-alt.svg');

                    return '
                        <a href="' . $viewUrl . '" class="circleIcon svg-bg" data-bs-toggle="tooltip" title="View">
                            <img src="' . $eyeIcon . '" alt="View" loading="lazy" />
                        </a>

                        <a href="' . $invoiceUrl . '" class="circleIcon btn-outline-secondary" data-bs-toggle="tooltip" title="Invoice">
                            <img src="' . $downIcon . '" alt="Invoice" loading="lazy" />
                        </a>
                    ';
                })


                ->rawColumns(['status', 'action', 'payment_method', 'total_amount', 'thumbnail'])
                ->make(true);
        }

        return view('admin.order.index');
    }
    // public function index($status = null)
    // {
    //     $status = $status ? str_replace('_', ' ', $status) : '';

    //     $generaleSetting = GeneraleSetting::first();
    //     $shop = null;
    //     if ($generaleSetting?->shop_type == 'single') {
    //         $shop = User::role(Roles::ROOT->value)->first()?->shop;
    //     }

    //     $orders = OrderRepository::query()
    //         ->when($shop, function ($query) use ($shop) {
    //             return $query->where('shop_id', $shop->id);
    //         })
    //         ->when($status, function ($query) use ($status) {
    //             $query->where('order_status', $status);
    //         })->latest('id')->paginate(20);

    //     return view('admin.order.index', compact('orders', 'status'));
    // }

    /**
     * Display the order details.
     */

    // public function show(Order $order)
    // {
    //     $order->load('address.stateData', 'address.districtData');

    //     $orderStatus = OrderStatus::cases();

    //     $riders = Driver::whereHas('user', function ($query) {
    //         $query->where('is_active', true);
    //     })->get();

    //     return view('shop.order.show', compact('order', 'orderStatus', 'riders'));
    // }

    public function show(Order $order)
    {
        // $order = Order::whereId($orderId)->firstOrFail(); 
        $orderId = $order->id;

        $order = Order::whereId($orderId)->firstOrFail()->load('address.stateData', 'address.districtData', 'shop.deliverySetting');


        $orderStatus = OrderStatus::cases();

        $riders = Driver::whereHas('user', function ($query) {
            $query->where('is_active', true);
        })->get();

        $deliverySetting = $order->shop->deliverySetting;
        $isManualDelivery = $deliverySetting && $deliverySetting->delivery_mode === 'manual';

        return view('shop.order.show', compact('order', 'orderStatus', 'riders', 'isManualDelivery'));
    }


    // public function show(Order $order)
    // {
    //     $orderStatus = OrderStatus::cases();

    //     $riders = Driver::whereHas('user', function ($query) {
    //         return $query->where('is_active', true);
    //     })->get();

    //     return view('admin.order.show', compact('order', 'orderStatus', 'riders'));
    // }

    /**
     * Update the order status.
     */
    public function statusChange(Order $order, Request $request)
    {
        $request->validate(['status' => 'required']);

        $order->update(['order_status' => $request->status]);

        OrderStatusTimeline::updateOrCreate(
            [
                'order_id' => $order->id,
                'status' => $request->status,
            ],
            [
                'changed_at' => Carbon::now(),
            ]
        );

        $title = 'Order status updated';
        $message = 'Your order status updated to ' . $request->status;
        $deviceKeys = $order->customer->user->devices->pluck('key')->toArray();

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

        try {
            NotificationServices::sendNotification($message, $deviceKeys, $title);
        } catch (\Throwable $th) {
        }

        $notify = (object) [
            'title' => $title,
            'content' => $message,
            'user_id' => $order->customer->user_id,
            'type' => 'order',
        ];

        NotificationRepository::storeByRequest($notify);

        return back()->with('success', __('Order status updated successfully.'));
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

        $title = 'Payment status updated';
        $message = __('Your payment status updated to paid. order code: ') . $order->prefix . $order->order_code;
        $deviceKeys = $order->customer->user->devices->pluck('key')->toArray();

        try {
            NotificationServices::sendNotification($message, $deviceKeys, $title);
        } catch (\Throwable $th) {
        }

        $notify = (object) [
            'title' => $title,
            'content' => $message,
            'user_id' => $order->customer->user_id,
            'type' => 'order',
        ];

        NotificationRepository::storeByRequest($notify);

        return back()->with('success', __('Payment status updated successfully'));
    }
}

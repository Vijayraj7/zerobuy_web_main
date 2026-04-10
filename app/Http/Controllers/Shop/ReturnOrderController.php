<?php

namespace App\Http\Controllers\Shop;

use App\Models\ReturnOrder;
use Illuminate\Http\Request;
use App\Enums\ReturnOderStatus;
use App\Http\Controllers\Controller;
use App\Models\ReturnOrderStatusTimeline;
use App\Repositories\NotificationRepository;
use App\Repositories\ReturnOrderRepository;
use App\Services\NotificationServices;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class ReturnOrderController extends Controller
{
    private function allowedNextStatuses(string $currentStatus): array
    {
        return match ($currentStatus) {
            ReturnOderStatus::PENDING->value => [
                ReturnOderStatus::APPROVED->value,
                ReturnOderStatus::REJECTED->value,
            ],
            ReturnOderStatus::APPROVED->value => [
                ReturnOderStatus::COMPLETED->value,
            ],
            default => [],
        };
    }

    public function index(Request $request)
    {
        return view('shop.returnOrder.index');
    }


    public function showall()
    {
        $shop = generaleSetting('shop');
        $shopId = $shop?->id;

        if (! $shopId) {
            return $this->json('Return details', ['return_orders' => []]);
        }
        $returnOrders = ReturnOrderRepository::query()
            ->where('shop_id', $shopId)
            ->with(['order', 'customer.user', 'returnProduct.product.media'])
            ->latest('id')
            ->get();

        $data = $returnOrders->map(function ($order) {
            $statusClass = match ($order->status) {
                'Pending' => 'warning',
                'Approved' => 'info',
                'Completed' => 'success',
                'Rejected', 'Cancelled' => 'danger',
                default => 'secondary',
            };

            $firstProduct = $order->returnProduct?->first()?->product;
            $thumbnail = $firstProduct?->thumbnail ?? asset('default/default.jpg');

            return [
                'id' => $order->id,
                'thumbnail' => $thumbnail,
                'return_date' => $order->created_at?->format('d M Y, h:i A') ?? '-',
                'order_id' => ($order->order?->prefix ?? '') . ($order->order?->order_code ?? ''),
                'return_code' => $order->return_code ?: ('RTN0' . $order->id),
                'order_date' => $order->order?->created_at?->format('d M Y, h:i A') ?? '-',
                'customer_name' => $order->customer?->user?->name ?? '-',
                'mobile_no' => $order->customer?->user?->phone ?? '-',
                'quantity' => $order->returnProduct?->sum('quantity') ?? 0,
                'amount' => showCurrency(optional($order->returnProduct?->first())->price ?? 0),
                'total_amount' => showCurrency($order->amount ?? 0),
                'reason' => $order->reason ?? '-',
                'status' => $order->status ?? '-',
                'status_class' => $statusClass,
                'details_url' => route('shop.returnOrder.show', $order->id),
            ];
        })->values();

        return $this->json('Return details', ['return_orders' => $data]);
    }


    public function show(ReturnOrder $returnOrder)
    {
        if ($returnOrder->shop_id != auth()->user()->shop->id) {
            //  abort(404);
        }

        $allowedNextStatuses = $this->allowedNextStatuses((string) $returnOrder->status);

        return view('shop.returnOrder.show', compact('returnOrder', 'allowedNextStatuses'));
    }

    public function refundIndex()
    {
        $shopId = auth()->user()->shop->id;
        $returnOrder = ReturnOrderRepository::query()->where('shop_id', $shopId)->where('payment_status', 1)->latest('id')->paginate(20);
        return view('shop.returnOrder.index', compact('returnOrder'));
    }

    public function statusChange(ReturnOrder $returnOrder, Request $request)
    {
        $shopId = auth()->user()->shop->id;

        if ($returnOrder->shop_id != $shopId) {
            abort(404);
        }

        if ($returnOrder->payment_status == 1) {
            return back()->with('error', __('Already paid for this order'));
        }

        $allowedNextStatuses = $this->allowedNextStatuses((string) $returnOrder->status);

        if (empty($allowedNextStatuses)) {
            return back()->with('error', __('Status cannot be changed from current state'));
        }

        $request->validate([
            'status' => ['required', Rule::in($allowedNextStatuses)],
        ]);

        $previousStatus = (string) $returnOrder->status;
        $returnOrder->update(['status' => $request->status]);

        $isNowRestockEligible = in_array((string) $request->status, [
            ReturnOderStatus::APPROVED->value,
            ReturnOderStatus::COMPLETED->value,
        ], true);
        $wasAlreadyRestocked = in_array($previousStatus, [
            ReturnOderStatus::APPROVED->value,
            ReturnOderStatus::COMPLETED->value,
        ], true);

        if ($isNowRestockEligible && !$wasAlreadyRestocked) {
            ReturnOrderRepository::restockReturnedProducts($returnOrder);
        }

        ReturnOrderStatusTimeline::updateOrCreate(
            [
                'return_order_id' => $returnOrder->id,
                'status' => $request->status,
            ],
            [
                'changed_at' => Carbon::now(),
            ]
        );


        $title = 'Return ' . $request->status;
        $message =  'Return Status Updated to ' . $request->status . ' for Return #' . $returnOrder->return_code;
        $deviceKeys = $returnOrder->customer->user->devices->pluck('key')->toArray();

        $noty = null;
        try {
            $noty =  NotificationServices::sendNotification($message, $deviceKeys, $title);
        } catch (\Throwable $th) {
        }

        $notify = (object) [
            'title' => $title,
            'content' => $message,
            'user_id' => $returnOrder->customer->user_id,
            // 'shop_id' => $returnOrder->order->shop->id,
            'type' => 'return',
        ];

        NotificationRepository::storeByRequest($notify);


        return back()->with('success', __('Status updated successfully'));
    }

    public function paymentStatus(ReturnOrder $returnOrder)
    {
        $shopId = auth()->user()->shop->id;

        if ($returnOrder->shop_id != $shopId) {
            abort(404);
        }

        if ($returnOrder->status == 'Pending') {
            return back()->with('error', __('Return order is not approved yet'));
        }
        if (in_array($returnOrder->status, ['Cancelled', 'Rejected'], true)) {
            return back()->with('error', __('Return order is not eligible for payment update'));
        }
        if ($returnOrder->payment_status == 1) {
            return back()->with('error', __('Payment status updated successfully'));
        }

        $returnOrder->update(['payment_status' => 1]);

        return back()->with('success', __('Payment status updated successfully'));
    }
}

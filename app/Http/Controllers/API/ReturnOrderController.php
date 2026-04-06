<?php

namespace App\Http\Controllers\API;

use App\Models\Order;
use App\Models\ReturnOrder;
use App\Models\OrderProduct;
use Illuminate\Http\Request;
use App\Models\ReturnOrderDetail;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReturnOrderRequest;
use App\Http\Resources\ReturnOrderResource;
use App\Repositories\ReturnOrderRepository;
use App\Http\Resources\ReturnOrderDetailsResource;
use App\Repositories\NotificationRepository;
use App\Services\NotificationServices;

class ReturnOrderController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->page;
        $perPage = $request->per_page;
        $skip = ($page * $perPage) - $perPage;

        $customer = auth()->user()->customer;

        $returnOrders = $customer->returnOrders()->latest('id');

        $total = $returnOrders->count();

        // paginate
        $returnOrders = $returnOrders->when($perPage && $page, function ($query) use ($perPage, $skip) {
            return $query->skip($skip)->take($perPage);
        })->get();

        return $this->json('returnOrders', [
            'total' => $total,
            'returnOrders' => ReturnOrderResource::collection($returnOrders),
        ]);
    }

    public function show(ReturnOrder $returnOrder)
    {
        $customer = auth()->user()->customer;

        $returnOrder = $customer->returnOrders()->where('id', $returnOrder->id)->first();

        return $this->json('returnOrders', [
            'returnOrders' => ReturnOrderDetailsResource::make($returnOrder),
        ]);
    }

    public function store(ReturnOrderRequest $request)
    {
        $order = Order::where('id', $request->order_id)->first();

        if ($order->order_status->value != 'Delivered') {
            return $this->json("This Order is not Delivered yet", [], 422);
        }

        foreach ($request->order_product_ids as $productId) {
            $orderProduct = $order->products()->wherePivot('id', $productId)->first();
            $days = $orderProduct->pivot->return_days;

            if ($order->created_at->diffInDays(now()) > $days) {
                return $this->json("Cannot return order after {$days} days", [], 422);
            }

            if (! $orderProduct) {
                return $this->json("Product with ID {$productId} not found in this order", [], 422);
            }
        }

        $alreadyReturned = ReturnOrderDetail::whereIn('order_product_id', $request->order_product_ids)
            ->whereHas('returnOrder', function ($q) use ($request) {
                $q->where('order_id', $request->order_id);
            })
            ->pluck('order_product_id')
            ->toArray();

        if (!empty($alreadyReturned)) {
            return $this->json("Products with IDs " . implode(', ', $alreadyReturned) . " already returned", [], 422);
        }

        $returnOrder = ReturnOrderRepository::storeByRequest($request);

        return $this->json('Order return successfully done', [
            'returnOrder' => ReturnOrderResource::make($returnOrder),
        ]);
    }

    public function cancel(ReturnOrder $returnOrder)
    {
        $customer = auth()->user()->customer;

        $returnOrder = $customer->returnOrders()->where('id', $returnOrder->id)->first();

        if (!$returnOrder) {
            return $this->json('Return order not found', [], 404);
        }

        if ($returnOrder->status === 'Cancelled') {
            return $this->json('Return order is already cancelled', [], 422);
        }

        if (in_array($returnOrder->status, ['Completed', 'Rejected'])) {
            return $this->json('Cannot cancel this return order', [], 422);
        }

        $returnOrder->update(['status' => 'Cancelled']);

        $title = 'Return Cancelled';
        $message =  'Return Cancelled by User';
        $deviceKeys = $returnOrder->order->shop->user->devices->pluck('key')->toArray();

        $noty = null;
        try {
            $noty =  NotificationServices::sendNotificationToTopic($message, 'topic_seller_' . $returnOrder->order->shop->id, $title);
        } catch (\Throwable $th) {
        }

        $notify = (object) [
            'title' => $title,
            'content' => $message,
            'user_id' => $returnOrder->order->shop->user_id,
            'shop_id' => $returnOrder->order->shop->id,
            'type' => 'return',
        ];

        NotificationRepository::storeByRequest($notify);

        return $this->json('Return order cancelled successfully', [
            'returnOrder' => ReturnOrderDetailsResource::make($returnOrder),
        ]);
    }
}

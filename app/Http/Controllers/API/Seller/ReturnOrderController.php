<?php

namespace App\Http\Controllers\API\Seller;

use App\Models\ReturnOrder;
use App\Models\ReturnOrderStatusTimeline;
use Illuminate\Http\Request;
use App\Enums\ReturnOderStatus;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rules\Enum;
use App\Http\Resources\ReturnOrderResource;
use App\Http\Resources\ReturnOrderDetailsResource;
use App\Repositories\NotificationRepository;
use App\Services\NotificationServices;
use Carbon\Carbon;

class ReturnOrderController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->page;
        $perPage = $request->per_page;
        $skip = ($page * $perPage) - $perPage;
        $shop = auth()->user()->shop;

        $returnOrders = $shop->returnOrders()->latest('id');

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
        $shop = auth()->user()->shop;

        $returnOrder = $shop->returnOrders()->where('id', $returnOrder->id)->first();

        if ($returnOrder->shop_id != $shop->id) {
            return $this->json('error', __('You do not have permission to view this order'));
        }

        return $this->json('returnOrders', [
            'returnOrders' => ReturnOrderDetailsResource::make($returnOrder),
        ]);
    }

    public function statusChange(Request $request)
    {
        $request->validate(['status' => ['required', new Enum(ReturnOderStatus::class)]]);

        $returnOrder = ReturnOrder::where('id', $request->order_id)->first();
        if (!$returnOrder) {
            return $this->json('error', __('Not Found'));
        }

        if ($returnOrder->payment_status == 1) {
            return $this->json('error', __('Already paid for this order'));
        }

        $shop = auth()->user()->shop;

        if ($returnOrder->shop_id != $shop->id) {
            return $this->json('error', __('You do not have permission to update this order'));
        }

        $returnOrder->update(['status' => $request->status]);

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
        $message =  'Return Status Updated to ' . $request->status . ' for Return #' . $returnOrder->id;
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


        return $this->json('success', __('Status updated successfully'));
    }
}

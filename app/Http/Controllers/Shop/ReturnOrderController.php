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

class ReturnOrderController extends Controller
{
    public function index()
    {
        $shopId = auth()->user()->shop->id;
        $returnOrder = ReturnOrderRepository::query()->where('shop_id', $shopId)->latest('id')->paginate(20);
        return view('shop.returnOrder.index', compact('returnOrder'));
    }


    public function showall()
    {
        $shopId = auth()->user()->shop->id;
        $returnOrder = ReturnOrderRepository::query()->where('shop_id', $shopId)->latest('id')->paginate(20);
        // return view('shop.returnOrder.index', compact('returnOrder'));
        return $this->json('Retur details', ['return' => $returnOrder]);
    }


    public function show(ReturnOrder $returnOrder)
    {
        if ($returnOrder->shop_id != auth()->user()->shop->id) {
            //  abort(404);
        }
        $returnStatus = ReturnOderStatus::cases();
        return view('shop.returnOrder.show', compact('returnOrder', 'returnStatus'));
    }

    public function refundIndex()
    {
        $shopId = auth()->user()->shop->id;
        $returnOrder = ReturnOrderRepository::query()->where('shop_id', $shopId)->where('payment_status', 1)->latest('id')->paginate(20);
        return view('shop.returnOrder.index', compact('returnOrder'));
    }

    public function statusChange(ReturnOrder $returnOrder, Request $request)
    {
        $request->validate(['status' => 'required']);

        $shopId = auth()->user()->shop->id;

        if ($returnOrder->shop_id != $shopId) {
            abort(404);
        }

        if ($returnOrder->payment_status == 1) {
            return back()->with('error', __('Already paid for this order'));
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
}

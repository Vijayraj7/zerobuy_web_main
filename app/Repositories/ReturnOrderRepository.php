<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\ReturnOrder;
use App\Models\OrderProduct;
use App\Enums\ReturnOderStatus;
use Abedin\Maker\Repositories\Repository;
use App\Services\NotificationServices;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReturnOrderRepository extends Repository
{
    public static function model()
    {
        return ReturnOrder::class;
    }


    public static function storeByRequest($request)
    {
        $order = Order::find($request->order_id);
        $totalAmount = 0;

        do {
            $returnCode = strtoupper(Str::random(10));
        } while (self::query()->where('return_code', $returnCode)->exists());

        $returnOrder = self::create([
            'return_code' => $returnCode,
            'order_id' => $request->order_id,
            'reason' => $request->reason,
            'bank_account_number' => $request->bank_account_number,
            'bank_name' => $request->bank_name,
            'return_address' => $request->return_address,
            'shop_id' => $order->shop_id,
            'customer_id' => auth()->user()->customer->id,
            'status' => ReturnOderStatus::PENDING->value
        ]);
        foreach ($request->products as $oproduct) {

            $orderProduct = $order->products()->wherePivot('order_products.id', $oproduct['order_product_id'])->first();

            $returnOrder->returnProduct()->create([
                'return_order_id' => $returnOrder->id,
                'product_id' => $orderProduct->id,
                'order_product_id' => $orderProduct->pivot->id,
                'price' => $orderProduct->pivot->price,
                'quantity' => $oproduct['quantity'],
                'color' => $orderProduct->pivot->orderVariant?->color_name ?? '',
                'size' => $orderProduct->pivot->orderVariant?->size_name ?? '',
                'unit' => $orderProduct->pivot->unit ?? '',
            ]);
            $qnty = (int)$oproduct['quantity'];
            $totalAmount += $orderProduct->pivot->price * $qnty;
        }

        try {
            // Handle image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('return_orders', 'public');
                    $returnOrder->returnProductImages()->create([
                        'image_path' => $path,
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log the error or handle it as needed
            Log::error('Return Order Image Upload Error: ' . $e->getMessage());
        }

        $returnOrder->amount = $totalAmount;
        $returnOrder->save();

        $title = 'New Return Received';
        $message =  'Return amount: ₹' . $returnOrder->amount;
        $deviceKeys = $order->shop->user->devices->pluck('key')->toArray();

        $noty = null;
        try {
            $noty =  NotificationServices::sendNotificationToTopic($message, 'topic_seller_' . $order->shop->id, $title);
        } catch (\Throwable $th) {
        }

        $notify = (object) [
            'title' => $title,
            'content' => $message,
            'user_id' => $order->shop->user_id,
            'shop_id' => $order->shop->id,
            'type' => 'return',
        ];

        NotificationRepository::storeByRequest($notify);

        return $returnOrder;
    }
}

<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\ReturnOrder;
use App\Models\OrderProduct;
use App\Enums\ReturnOderStatus;
use Abedin\Maker\Repositories\Repository;

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
        $returnOrder = self::create([
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

        // Handle image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('return_orders', 'public');
                $returnOrder->returnProductImages()->create([
                    'image_path' => $path,
                ]);
            }
        }

        $returnOrder->amount = $totalAmount;
        $returnOrder->save();
        return $returnOrder;
    }
}

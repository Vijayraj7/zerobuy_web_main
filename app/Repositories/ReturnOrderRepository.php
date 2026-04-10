<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\ReturnOrder;
use App\Models\OrderProduct;
use App\Models\ProductBulkItem;
use App\Models\ProductVariant;
use App\Models\ReturnOrderStatusTimeline;
use App\Enums\ReturnOderStatus;
use Abedin\Maker\Repositories\Repository;
use App\Services\NotificationServices;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RuntimeException;

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
            'bank_account_holder_name' => $request->bank_account_holder_name,
            'ifsc' => $request->ifsc,
            'upi_id' => $request->upi_id,
            'return_address' => $request->return_address,
            'shop_id' => $order->shop_id,
            'customer_id' => Auth::user()?->customer?->id,
            'status' => ReturnOderStatus::PENDING->value
        ]);

        ReturnOrderStatusTimeline::updateOrCreate(
            [
                'return_order_id' => $returnOrder->id,
                'status' => ReturnOderStatus::PENDING->value,
            ],
            [
                'changed_at' => $returnOrder->created_at,
            ]
        );

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
                try {
                    $path = $image->store('return_orders', 'public');
                    $returnOrder->returnProductImages()->create([
                        'image_path' => $path,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Return Order Image Upload Error', [
                        'return_order_id' => $returnOrder->id,
                        'file_name' => $image->getClientOriginalName(),
                        'message' => $e->getMessage(),
                    ]);

                    throw new RuntimeException('Failed to upload one or more return images. Please try with a smaller image or different format.');
                }
            }
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

    public static function restockReturnedProducts(ReturnOrder $returnOrder): void
    {
        $returnOrder->loadMissing([
            'returnProduct.orderProduct.product',
            'returnProduct.orderProduct.orderVariant',
            'returnProduct.orderProduct.orderBulkItem',
            'returnProduct.product',
        ]);

        foreach ($returnOrder->returnProduct as $returnProduct) {
            $restockQuantity = max(1, (int) ($returnProduct->quantity ?? 1));
            $orderProduct = $returnProduct->orderProduct;
            $baseProduct = $orderProduct?->product ?? $returnProduct->product;

            if (!$baseProduct) {
                continue;
            }

            if (!empty($orderProduct?->order_variants_id)) {
                $orderVariant = $orderProduct?->orderVariant;

                $variantQuery = ProductVariant::query()->where('product_id', $baseProduct->id);

                if (!empty($orderVariant?->color_name)) {
                    $colorName = trim((string) $orderVariant->color_name);
                    $variantQuery->whereHas('color', function ($query) use ($colorName) {
                        $query->where('name', $colorName);
                    });
                }

                if (!empty($orderVariant?->size_name)) {
                    $sizeName = trim((string) $orderVariant->size_name);
                    $variantQuery->whereHas('size', function ($query) use ($sizeName) {
                        $query->where('name', $sizeName);
                    });
                }

                if (is_numeric($orderVariant?->price ?? null)) {
                    $variantQuery->where('price', (float) $orderVariant->price);
                }

                $variant = $variantQuery->first();
                if ($variant) {
                    $variant->increment('quantity', $restockQuantity);
                } else {
                    Log::warning('Return restock skipped: matching product variant not found', [
                        'return_order_id' => $returnOrder->id,
                        'order_product_id' => $orderProduct?->id,
                        'product_id' => $baseProduct->id,
                    ]);
                }

                continue;
            }

            if (!empty($orderProduct?->order_bulk_items_id)) {
                $orderBulkItem = $orderProduct?->orderBulkItem;

                $bulkItemQuery = ProductBulkItem::query()->where('product_id', $baseProduct->id);

                if (!empty($orderBulkItem?->name)) {
                    $bulkItemQuery->where('name', trim((string) $orderBulkItem->name));
                }

                if (is_numeric($orderBulkItem?->selling_price ?? null)) {
                    $bulkItemQuery->where('selling_price', (float) $orderBulkItem->selling_price);
                }

                if (is_numeric($orderBulkItem?->mrp ?? null)) {
                    $bulkItemQuery->where('mrp', (float) $orderBulkItem->mrp);
                }

                $bulkItem = $bulkItemQuery->first();
                if ($bulkItem) {
                    $bulkItem->increment('quantity', $restockQuantity);
                } else {
                    Log::warning('Return restock skipped: matching product bulk item not found', [
                        'return_order_id' => $returnOrder->id,
                        'order_product_id' => $orderProduct?->id,
                        'product_id' => $baseProduct->id,
                    ]);
                }

                continue;
            }

            $baseProduct->increment('quantity', $restockQuantity);
        }
    }
}

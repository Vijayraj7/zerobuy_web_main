<?php

namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderMailEvent;
use App\Http\Requests\OrderRequest;
use App\Models\Address;
use App\Models\AdminCoupon;
use App\Models\GeneraleSetting;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderBulkItem;
use App\Models\OrderedProduct;
use App\Models\OrderVariant;
use App\Models\OrderVatTax;
use App\Models\Payment;
use App\Models\Shop;
use App\Services\NotificationServices;
use Illuminate\Support\Facades\Log;

class OrderRepository extends Repository
{
    /**
     * base method
     *
     * @method model()
     */
    public static function model()
    {
        return Order::class;
    }

    public static function getShopSales($shopId)
    {
        return self::query()->withoutGlobalScopes()->where('shop_id', $shopId)->get();
    }

    /**
     * Store new order from cart
     */
    public static function storeByRequestFromCart(OrderRequest $request, $paymentMethod, $carts): Payment
    {
        $totalPayableAmount = 0;

        $payment = Payment::create([
            'amount' => $totalPayableAmount,
            'payment_method' => $request->payment_method,
        ]);

        $shopProducts = $carts->groupBy('shop_id');

        foreach ($shopProducts as $shopId => $cartProducts) {

            $shop = Shop::find($shopId);

            $getCartAmounts = self::getCartWiseAmounts($shop, collect($cartProducts), $request->coupon_code);

            $order = self::createNewOrder($request, $shop, $paymentMethod, $getCartAmounts);

            $totalPayableAmount += $getCartAmounts['payableAmount'];
            $payment->orders()->attach($order->id);

            foreach ($cartProducts as $cart) {

                if ($cart->variant != null) {
                    $cart->variant->decrement('quantity', $cart->quantity);
                } elseif ($cart->bulkItem != null) {
                    $cart->bulkItem->decrement('quantity', $cart->quantity);
                } else {
                    $cart->product->decrement('quantity', $cart->quantity);
                }

                $product = $cart->product;
                // $productx = $cart->product;

                // $productxx = OrderedProduct::create([
                //     // 'id' => $productx->id,
                //     // Reference to original product
                //     'product_id' => $productx->id,

                //     // Basic info
                //     'name' => $productx->name,
                //     'name_ar' => $productx->name_ar ?? null,
                //     'slug' => $productx->slug,
                //     'code' => $productx->sku ?? null, // mapped from sku

                //     'condition_status' => $productx->condition_status ?? 'New',

                //     // Relations
                //     'shop_id' => $productx->shop_id,
                //     'media_id' => $productx->media_id ?? null,
                //     'brand_id' => $productx->brand_id ?? null,
                //     'unit_id' => $productx->unit_id ?? null,
                //     'video_id' => $productx->video_id ?? null,

                //     // Pricing & stock
                //     'price' => $productx->price,
                //     'buy_price' => $productx->buy_price ?? 0,
                //     'discount_price' => $productx->discount_price,
                //     'quantity' => $productx->quantity ?? 0,
                //     'min_order_quantity' => $productx->min_order_quantity ?? 1,
                //     'return_period' => $productx->return_period ?? null,
                //     'tax_percentage' => $productx->tax_percentage ?? null,

                //     // Descriptions (JSON-safe)
                //     'details' => $productx->details
                //         ? json_encode($productx->details, JSON_UNESCAPED_UNICODE)
                //         : json_encode([]),

                //     'short_description' => $productx->short_description ?? null,
                //     'short_description_ar' => $productx->short_description_ar ?? null,
                //     'description' => $productx->description ?? null,
                //     'description_ar' => $productx->description_ar ?? null,

                //     // Flags
                //     'is_active' => 1,
                //     'is_new' => $productx->is_new ?? 0,
                //     'is_featured' => $productx->is_featured ?? 0,
                //     'is_approve' => 1,

                //     // SEO
                //     'meta_title' => $productx->meta_title ?? $productx->name,
                //     'meta_description' => $productx->meta_description ?? null,
                //     'meta_keywords' => $productx->meta_keywords ?? null,
                // ]);

                $price = $product->discount_price > 0 ? $product->discount_price : $product->price;

                $flashSale = $product->flashSales?->first();
                $flashSaleProduct = null;
                $quantity = 0;

                $saleQty = $cart->quantity;

                if ($flashSale) {
                    $flashSaleProduct = $flashSale?->products()->where('id', $product->id)->first();

                    $quantity = $flashSaleProduct?->pivot->quantity - $flashSaleProduct->pivot->sale_quantity;

                    if ($quantity == 0) {
                        $flashSaleProduct = null;
                    } else {
                        $price = $flashSaleProduct->pivot->price;
                        $saleQty += $flashSaleProduct->pivot->sale_quantity;

                        $flashSale->products()->updateExistingPivot($product->id, [
                            'sale_quantity' => $saleQty,
                        ]);
                    }
                }

                $sizePrice = $product->sizes()?->where('id', $cart->size)->first()?->pivot?->price ?? 0;
                $price = $price + $sizePrice;

                $colorPrice = $product->colors()?->where('id', $cart->color)->first()?->pivot?->price ?? 0;
                $price = $price + $colorPrice;

                $size = $product->sizes()?->where('id', $cart->size)->first();
                $color = $product->colors()?->where('id', $cart->color)->first();
                $order_variant_id = null;
                $order_bulk_item_id = null;
                if ($cart->variant) {
                    $order_variant_id = OrderVariant::create([
                        'color_name' => $cart->variant->color->name,
                        'color_code' => $cart->variant->color->color_code,
                        'size_name' => $cart->variant->size?->name,
                        'price' => $cart->variant->price,
                        'quantity' => $cart->quantity,
                    ])->id;
                }

                if ($cart->bulkItem) {
                    $order_bulk_item_id = OrderBulkItem::create([
                        'name' => $cart->bulkItem->name,
                        'quantity' => $cart->quantity,
                        'moq' => $cart->bulkItem->moq,
                        'mrp' => $cart->bulkItem->mrp,
                        'selling_price' => $cart->bulkItem->selling_price,
                    ])->id;
                }

                $order->products()->attach($product->id, [
                    'order_variants_id' => $order_variant_id,
                    'order_bulk_items_id' => $order_bulk_item_id,
                    'quantity' => $cart->quantity,
                    'product_name' => $product->name,
                    'return_days' => $product->return_period,
                    'color' => $cart->variant?->color?->name ?? $color?->name,
                    'size' => $cart->variant?->size?->name ?? $size?->name,
                    'unit' => $cart->unit,
                    'price' => $cart->variant?->price ?? $cart->bulkItem?->selling_price ?? $price,
                ]);
            }

            foreach ($getCartAmounts['allVatTaxes'] ?? [] as $vatTax) {
                if (! $vatTax) continue;

                OrderVatTax::create([
                    'order_id' => $order->id,
                    'name' => $vatTax->name,
                    'percentage' => $vatTax->percentage,
                    'amount' => $vatTax->amount,
                ]);
            }

            $user = auth()->user();
            if ($user?->email) {
                try {
                    OrderMailEvent::dispatch($user->email, $order);
                } catch (\Throwable $th) {
                }
            }
        }

        $payment->update([
            'amount' => $totalPayableAmount,
        ]);

        $isBuyNow = $request->is_buy_now ?? false;
        $customer = auth()->user()->customer;

        $customer->carts()->whereIn('shop_id', $request->shop_ids)->where('is_buy_now', $isBuyNow)->delete();

        return $payment;
    }

    private static function createNewOrder($request, $shop, $paymentMethod, $getCartAmounts)
    {
        $lastOrderId = self::query()->max('id');

        $address = Address::find($request->address_id);

        $orderaddress_id = OrderAddress::create([
            'name' => $address->name,
            'phone' => $address->phone,
            'customer_id' => $address->customer_id,
            'address_type' => $address->address_type,
            'area' => $address->area,
            'road_no' => $address->road_no,
            'flat_no' => $address->flat_no,
            'house_no' => $address->house_no,
            'address_line' => $address->address_line,
            'address_line2' => $address->address_line2,
            'post_code' => $address->post_code,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'state' => $address->state,
            'state_id' => $address->state_id,
            'district_id' => $address->district_id,
            'is_default' => $address->is_default,
        ])->id;

        $order = self::create([
            'shop_id' => $shop->id,
            'order_code' => str_pad($lastOrderId + 1, 6, '0', STR_PAD_LEFT),
            'prefix' => $shop->prefix ?? 'RC',
            'gst' => $request->gst,
            'customer_id' => auth()->user()->customer->id,
            'coupon_id' => $getCartAmounts['coupon'],
            'delivery_charge' => $getCartAmounts['deliveryCharge'],
            'payable_amount' => $getCartAmounts['payableAmount'],
            'total_amount' => $getCartAmounts['totalAmount'],
            'tax_amount' => $getCartAmounts['totalTaxAmount'],
            'coupon_discount' => $getCartAmounts['discount'],
            'payment_method' => $paymentMethod->value,
            'order_status' => OrderStatus::PENDING->value,
            'address_id' => $orderaddress_id,
            'instruction' => $request->note,
            'payment_status' => PaymentStatus::PENDING->value,
        ]);

        $generalSetting = generaleSetting('setting');

        if ($generalSetting?->business_based_on == 'subscription') {
            $subscription = $shop->currentSubscription;

            if ($subscription && $subscription->remaining_sales && $subscription->remaining_sales > 0) {
                $subscription->update([
                    'remaining_sales' => $subscription->remaining_sales - 1
                ]);
            }
        }

        return $order;
    }

    public static function getCartWiseAmounts(Shop $shop, $carts, $couponCode = null, $addressId = null): array
    {
        // Log::info('Controller address id', [
        //     'address_id' => $addressId,
        //     'all_request' => request()->all(),
        // ]);
        $totalAmount = 0;
        $discount = 0;
        $coupon = null;
        $totalTaxAmount = 0;

        $orderQty = $carts->sum('quantity');
        $deliveryCharge = getDeliveryCharge($orderQty);

        $allVatTaxes = [];

        foreach ($carts ?? [] as $cart) {

            if (! $cart) {
                continue;
            }

            $product = $cart->product;
            $price = $product->discount_price > 0 ? $product->discount_price : $product->price;

            if ($cart->variant != null) {
                $price = (float)$cart->variant->price;
            } else if ($cart->bulkItem) {
                $price = (float)$cart->bulkItem->selling_price;
            } else if ($product->bulkPrices) {
                $ogprice = $product->bulkPrices->where('min_qty', '<=', $cart->quantity)
                    ->where('max_qty', '>=', $cart->quantity)
                    ->first();
                if ($ogprice) {
                    $price =  (float) number_format($ogprice->price, 2, '.', '');
                } else {
                    $lastbulkprice = $product->bulkPrices->sortByDesc('max_qty')->first();
                    if ($lastbulkprice && $cart->quantity > $lastbulkprice->max_qty) {
                        $price =  (float) number_format($lastbulkprice->price, 2, '.', '');
                    }
                }
            }

            $flashSale = $product->flashSales?->first();
            $flashSaleProduct = null;
            $quantity = 0;

            if ($flashSale) {
                $flashSaleProduct = $flashSale?->products()->where('id', $product->id)->first();

                $quantity = $flashSaleProduct?->pivot->quantity - $flashSaleProduct->pivot->sale_quantity;

                if ($quantity == 0) {
                    $flashSaleProduct = null;
                } else {
                    $price = $flashSaleProduct->pivot->price;
                }
            }
            $sizePrice = $product->sizes()?->where('id', $cart->size)->first()->pivot?->price ?? 0;
            $price = $price + $sizePrice;

            $colorPrice = $product->colors()?->where('id', $cart->color)->first()->pivot?->price ?? 0;
            $price = $price + $colorPrice;

            $totalAmount += ($price * $cart->quantity);
        }

        $address = Address::find(request()->address_id ?? $addressId);
        $deliveryCharge = getShopDeliveryCharge($totalAmount, $shop, $address->state_id);

        // order vat taxes
        $vatTaxes = VatTaxRepository::getActiveVatTaxes();

        foreach ($vatTaxes ?? [] as $vatTax) {
            if ($vatTax?->name && $vatTax?->percentage > 0) {
                $taxAmount = round($totalAmount * ($vatTax->percentage / 100), 2);

                $allVatTaxes[] = (object) [
                    'name' => $vatTax->name,
                    'percentage' => $vatTax->percentage,
                    'amount' => $taxAmount,
                ];

                $totalTaxAmount += $taxAmount;
            }
        }

        // get coupon discount
        $couponDiscount = self::getCouponDiscount($totalAmount, $shop->id, $couponCode);

        // check coupon discount amount
        if ($couponDiscount['total_discount_amount'] > 0) {
            $discount += $couponDiscount['total_discount_amount'];
            $coupon = $couponDiscount['coupon'];
        }

        // calculate payable amount
        $payableAmount = ($totalAmount + $deliveryCharge + $totalTaxAmount) - $discount;

        // return array
        return [
            'totalAmount' => $totalAmount,
            'totalTaxAmount' => $totalTaxAmount,
            'payableAmount' => $payableAmount,
            'discount' => $discount,
            'deliveryCharge' => $deliveryCharge,
            'coupon' => $coupon?->id,
            'allVatTaxes' => $allVatTaxes,
        ];
    }

    /**
     * Creates a new order based on the provided order, generates a new order code,
     * and associates it with the corresponding shop orders and products.
     *
     * @param  Order  $order  The original order to be used as a base for the new order
     * @return Order The newly created order
     */
    public static function reOrder(Order $order, $payment): Order
    {
        $lastOrderId = self::query()->max('id');

        $newOrder = self::create([
            'shop_id' => $order->shop_id,
            'order_code' => str_pad($lastOrderId + 1, 6, '0', STR_PAD_LEFT),
            'prefix' => 'RC',
            'customer_id' => $order->customer_id,
            'coupon_id' => $order->coupon_id ?? null,
            'delivery_charge' => $order->delivery_charge,
            'payable_amount' => $order->payable_amount,
            'total_amount' => $order->total_amount,
            'tax_amount' => $order->tax_amount,
            'coupon_discount' => $order->coupon_discount,
            'payment_method' => $payment->payment_method ?? $order->payment_method,
            'order_status' => OrderStatus::PENDING->value,
            'address_id' => $order->address_id,
            'instruction' => $order->instruction,
            'payment_status' => PaymentStatus::PENDING->value,
        ]);

        foreach ($order->products as $product) {

            $qty = $product->pivot->quantity;

            $product->decrement('quantity', $qty);

            $newOrder->products()->attach($product->id, [
                'quantity' => $product->pivot->quantity,
                'color' => $product->pivot->color ?? null,
                'size' => $product->pivot->size ?? null,
                'unit' => $product->pivot->unit ?? null,
                'price' => $product->pivot->price,
            ]);
        }

        foreach ($order->vatTaxes ?? [] as $vatTax) {
            if (! $vatTax) {
                continue;
            }
            OrderVatTax::create([
                'order_id' => $newOrder->id,
                'name' => $vatTax->name,
                'percentage' => $vatTax->percentage,
                'amount' => $vatTax->amount,
            ]);
        }

        $user = auth()->user();
        if ($user?->email) {
            try {
                OrderMailEvent::dispatch($user->email, $newOrder);
            } catch (\Throwable $th) {
            }
        }

        return $order;
    }

    /**
     * Get applied coupon orders
     *
     * @param  mixed  $coupon
     * @return collection
     */
    public static function getAppliedCouponOrders($coupon)
    {
        return auth()->user()->customer?->orders()?->where('coupon_id', $coupon->id)->get();
    }

    /**
     * Get coupon discount
     *
     * @param  mixed  $totalAmount
     * @param  mixed  $shopId
     * @param  mixed  $couponCode
     * @return array
     */
    public static function getCouponDiscount($totalAmount, $shopId, $couponCode = null)
    {
        $totalOrderAmount = 0;
        $totalDiscountAmount = 0;
        $coupon = null;

        if ($couponCode) {
            $shop = Shop::find($shopId);
            $coupon = $shop->coupons()->where('code', $couponCode)->Active()->isValid()->first();

            if (! $coupon) {
                $coupon = AdminCoupon::where('shop_id', $shopId)->whereHas('coupon', function ($query) use ($couponCode) {
                    $query->where('code', $couponCode)->Active()->isValid();
                })->first()?->coupon;
            }

            if ($coupon) {
                $discount = self::getCouponDiscountAmount($coupon, $totalAmount);

                $totalOrderAmount += $discount['total_amount'];
                $totalDiscountAmount += $discount['discount_amount'];
            }
        } else {

            $collectedCoupons = CouponRepository::getCollectedCoupons($shopId);

            foreach ($collectedCoupons as $collectedCoupon) {

                $discount = self::getCouponDiscountAmount($collectedCoupon, $totalAmount);

                $totalOrderAmount += $discount['total_amount'];

                if ($discount['discount_amount'] > 0) {
                    $coupon = $collectedCoupon;
                    $totalDiscountAmount += $discount['discount_amount'];
                    break;
                }
            }
        }

        return [
            'total_order_amount' => $totalOrderAmount,
            'total_discount_amount' => $totalDiscountAmount,
            'coupon' => $coupon,
        ];
    }

    /**
     * Get coupon discount amount
     *
     * @param  mixed  $coupon
     * @param  mixed  $totalAmount
     * @return array
     */
    private static function getCouponDiscountAmount($coupon, $totalAmount)
    {
        $appliedOrders = self::getAppliedCouponOrders($coupon);

        $amount = $coupon->type->value == DiscountType::PERCENTAGE->value ? ($totalAmount * $coupon->discount) / 100 : $coupon->discount;

        $couponDiscount = 0;
        if ($appliedOrders->count() < ($coupon->limit_for_user ?? 500) && $coupon->min_amount <= $totalAmount) {
            $couponDiscount = $amount;
            if ($coupon->max_discount_amount && $coupon->max_discount_amount < $amount) {
                $couponDiscount = $coupon->max_discount_amount;
            }
        }

        return [
            'total_amount' => $totalAmount,
            'discount_amount' => (float) round($couponDiscount ?? 0, 2),
        ];
    }

    /**
     * Order status update from rider
     */
    public static function OrderStatusUpdateFromRider(Order $order, $driverOrder, $orderStatus)
    {
        if ($orderStatus == OrderStatus::PROCESSING->value) {
            $driverOrder->update(['is_accept' => true]);
        }

        $order->update([
            'order_status' => ($orderStatus == 'deliveredAndPaid') ? OrderStatus::DELIVERED->value : $orderStatus,
        ]);

        if ($orderStatus == OrderStatus::PICKUP->value) {
            $order->update([
                'pick_date' => now(),
                'order_status' => OrderStatus::ON_THE_WAY->value,
            ]);
        }

        $paymentMethod = $order->payment_method->value;

        $isDelivery = false;
        if ($paymentMethod != PaymentMethod::CASH->value && $orderStatus == OrderStatus::DELIVERED->value) {
            $isDelivery = true;
        }

        if (($orderStatus == 'deliveredAndPaid') || $isDelivery) {

            $driverOrder->update(['is_completed' => true]);

            if ($paymentMethod == PaymentMethod::CASH->value) {
                $driverOrder->update(['cash_collect' => true]);

                $totalCashCollected = $driverOrder->driver->total_cash_collected + $order->payable_amount;

                $driverOrder->driver->update([
                    'total_cash_collected' => $totalCashCollected,
                ]);
            }

            $generaleSetting = GeneraleSetting::first();

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

            $wallet = $order->shop->user->wallet;

            WalletRepository::updateByRequest($wallet, $order->total_amount, 'credit');

            if ($generaleSetting?->business_based_on == 'commission') {
                TransactionRepository::storeByRequest($wallet, $commission, 'debit', true, true, 'admin commission added', 'order commission added in admin wallet', $order->id, null);
            }

            $driverWallet = DriverRepository::getWallet($driverOrder->driver);

            $deliveryCharge = $order->delivery_charge;

            WalletRepository::updateByRequest($driverWallet, $deliveryCharge, 'credit');
        }

        $user = $order->customer?->user;

        $message = "Hello {$user?->name}. Your order status is {$orderStatus}. OrderID: {$order->prefix}{$order->order_code}";

        $title = 'Order Status Update';

        $devices = $user?->devices;

        if (count($devices) > 0) {

            $deviceKeys = $devices->pluck('key')->toArray();
            try {
                NotificationServices::sendNotification($message, $deviceKeys, $title);
            } catch (\Throwable $th) {
            }
        }

        NotificationRepository::storeByRequest((object) [
            'title' => $title,
            'content' => $message,
            'user_id' => $user?->id,
            'url' => $order->id,
            'type' => 'order',
            'icon' => null,
            'is_read' => false,
        ]);
    }
}

<?php

namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Http\Requests\CartRequest;
use App\Http\Resources\CartBulkItemResource;
use App\Http\Resources\CartVariantResource;
use App\Http\Resources\ColorResource;
use App\Http\Resources\ProductBulkItemResource;
use App\Http\Resources\ProductVariantResource;
use App\Http\Resources\SizeResource;
use App\Models\Cart;
use App\Models\CartBulkItem;
use App\Models\CartVariant;
use App\Models\Address;
use App\Models\DeliveryCharge;
use App\Models\DeliverySetting;
use App\Models\Product;
use App\Models\ProductBulkItem;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class CartRepository extends Repository
{
    private static function resolveStateIdFromRequest($request = null): ?int
    {
        $stateId = data_get($request, 'state_id', request()->input('state_id'));
        if ($stateId !== null && $stateId !== '') {
            return (int) $stateId;
        }

        $addressId = data_get($request, 'address_id', request()->input('address_id'));
        if (! $addressId) {
            return null;
        }

        $address = Address::query()->find($addressId);

        return $address?->state_id ? (int) $address->state_id : null;
    }

    public static function model()
    {
        return Cart::class;
    }

    public static function ShopWiseCartProducts($groupCart, $request = null)
    {
        $totalItems = 0;
        $shopWiseProducts = collect([]);
        $info = null;

        foreach ($groupCart as $key => $products) {
            $productArray = collect([]);
            $totalAmount = 0;
            $deliveryCharge = 0;
            foreach ($products as $cart) {

                $product = $cart->product;

                if (! $product) {
                    $cart->delete();
                    $info = 'Some products are removed from cart due to unavailability';
                    continue;
                }

                $totalItems++;

                $discountPercentage = $product->getDiscountPercentage($product->price, $product->discount_price);

                $totalSold = $product->orders->sum('pivot.quantity');

                $flashSale = $product->flashSales?->first();
                $flashSaleProduct = null;
                $quantity = null;

                if ($flashSale) {
                    $flashSaleProduct = $flashSale?->products()->where('id', $product->id)->first();

                    $quantity = $flashSaleProduct?->pivot->quantity - $flashSaleProduct->pivot->sale_quantity;

                    if ($quantity == 0) {
                        $quantity = null;
                        $flashSaleProduct = null;
                    } else {
                        $discountPercentage = $flashSale?->pivot->discount;
                    }
                }

                $size = $product->sizes()?->where('id', $cart->size)->first();
                $color = $product->colors()?->where('id', $cart->color)->first();

                $sizePrice = $size?->pivot?->price ?? 0;
                $colorPrice = $color?->pivot?->price ?? 0;
                $extraPrice = $sizePrice + $colorPrice;

                $flashPercentage = null;
                $discountPrice = $product->discount_price > 0 ? ($product->discount_price + $extraPrice) : 0;
                if ($flashSaleProduct) {
                    $flashPercentage = $flashSale?->pivot->discount;
                    // $discountPrice = $flashSaleProduct->pivot->price + $extraPrice;
                }

                $mainPrice = $product->price + $extraPrice;

                // calculate vat taxes
                $priceTaxAmount = 0;
                $discountTaxAmount = 0;
                foreach ($product->vatTaxes ?? [] as $tax) {
                    if ($tax->percentage > 0) {
                        $priceTaxAmount += $mainPrice * ($tax->percentage / 100);
                        $discountPrice > 0 ? $discountTaxAmount += $discountPrice * ($tax->percentage / 100) : null;
                    }
                }

                $mainPrice += $priceTaxAmount;
                $discountPrice > 0 ? $discountPrice += $discountTaxAmount : null;

                if ($discountPrice > 0) {
                    $discountPercentage = ($mainPrice - $discountPrice) / $mainPrice * 100;
                }

                $pname = $product->name;
                $dprice = 0;
                $mprice = (float) number_format($mainPrice, 2, '.', '');
                if ($cart->variant) {
                    $dprice =  (float) $cart->variant->price;
                } else if ($cart->bulkItem) {
                    $pname = $cart->bulkItem->name;
                    $mprice = (float) number_format($cart->bulkItem->mrp, 2, '.', '');
                    $dprice = (float) $cart->bulkItem->selling_price;
                } else if ($product->bulkPrices) {
                    if ($product->bulkPrices->count() > 0) {
                        $ogprice = $product->bulkPrices->where('min_qty', '<=', $cart->quantity)
                            ->where('max_qty', '>=', $cart->quantity)
                            ->first();
                        if ($ogprice) {
                            $dprice = (float) $ogprice->price;
                        } else {
                            $lastbulkprice = $product->bulkPrices->sortByDesc('max_qty')->first();
                            if ($lastbulkprice && $cart->quantity > $lastbulkprice->max_qty) {
                                $dprice =  (float) $lastbulkprice->price;
                            }
                        }
                    } else {
                        $dprice = (float) $discountPrice;
                    }
                } else {
                    $dprice = (float) $discountPrice;
                }

                if ($flashPercentage) {
                    $dprice = $dprice - ($dprice * ($flashPercentage / 100));
                    $dprice = (float) number_format($dprice, 2, '.', '');
                }

                // $product_quantity = $product->quantity;
                // $totalSold = 0;
                // $product_orders = $product->orders;
                // foreach ($product_orders as $order) {
                //     foreach ($order->orderProducts as $order_product) {
                //         if ($order_product->orderVariant) {
                //             if ($order_product->orderVariant->product_variants_id == $cart->variant_id) {
                //                 $product_quantity -= $order_product->quantity;
                //             }
                //         }
                //     }
                // }


                $product_quantity = $product->quantity;
                $product_min_quantity = $product->min_order_quantity ?? 1;
                if ($cart->variant != null) {
                    $product_quantity = $cart->variant->quantity;
                } elseif ($cart->bulkItem != null) {
                    $product_quantity = $cart->bulkItem->quantity;
                    $product_min_quantity = $cart->bulkItem->moq ?? 1;
                }

                $productArray[] = (object) [
                    'id' => $product->id,
                    'cart_id' => (int) $cart->id,
                    'quantity' => (int) $cart->quantity,
                    'product_quantity' => (int) $product_quantity,
                    'product_min_quantity' => (int) $product_min_quantity,
                    'name' => $pname,
                    'thumbnail' => $product->thumbnail,
                    'variant' => $cart->variant ? ProductVariantResource::make($cart->variant) : null,
                    'bulk_item' => $cart->bulkItem ? ProductBulkItemResource::make($cart->bulkItem) : null,
                    'brand' => $product->brand?->name ?? null,
                    'price' => $mprice,
                    'discount_price' => $dprice,
                    'discount_percentage' => (float) number_format($discountPercentage, 2, '.', ''),
                    'rating' => (float) $product->averageRating,
                    'total_reviews' => (string) Number::abbreviate($product->reviews->count(), maxPrecision: 2),
                    'total_sold' => (string) number_format($totalSold, 0, '.', ','),
                    'color' => $color ? ColorResource::make($color) : null,
                    'size' => $size ? SizeResource::make($size) : null,
                    'unit' => $cart->unit,
                ];
                $price = $dprice > 0 ? $dprice : $mprice;
                $totalAmount += $price * $cart->quantity;
            }

            if ($productArray->isEmpty()) {
                continue;
            }

            $shop = $products[0]?->shop;
            $stateId = self::resolveStateIdFromRequest($request);

            $lastOnline = (bool) ($shop?->isOnline() ?? false);

            $isDeliverable = true;
            $deliveryCharge = 0.00;
            $deliveryCharge = getShopDeliveryCharge($totalAmount, $shop, $stateId);
            if ($deliveryCharge === null) {
                $isDeliverable = false;
                $deliveryCharge = 0.00;
            }

            $checkout = CartRepository::checkoutByRequest($request, $products);

            $applyCoupon = false;

            $applyCoupon = false;
            if (request()->filled('coupon_code')) {
                if (request()->coupon_code && $checkout['coupon_discount'] > 0) {
                    $applyCoupon = true;
                    $message = 'Coupon applied';
                } elseif (request()->coupon_code) {
                    $message = 'Coupon not applied';
                }
            }

            $shopWiseProducts[] = (object) [
                'shop_id' => $key,
                'total_amount' => $totalAmount,

                // 'payable_amount' => $checkout['payable_amount'],
                'is_deliverable' => $checkout['is_deliverable'],

                'selected_state_ids' => DeliverySetting::where('shop_id', $shop?->id)->first()->selected_state_ids,
                'state_id' => $stateId,
                'is_dely' => in_array((string) $stateId, DeliverySetting::where('shop_id', $shop?->id)->first()->selected_state_ids),
                'delivery_mode' => DeliverySetting::where('shop_id', $shop?->id)->first()->delivery_mode,

                'totalAmount' => $checkout['total_amount'],
                'payableAmount' => $checkout['payable_amount'],
                'discount' => $checkout['coupon_discount'],
                // 'deliveryCharge' => $totalAmount,
                'applyCoupon' => $applyCoupon,
                // 'giftCharge' => $checkout['gift_charge'] ?? 0.00,
                'orderTaxAmount' => $checkout['order_tax_amount'],
                'allVatTaxes' => $checkout['all_vat_taxes'],

                'is_deliverable' => $isDeliverable,
                // 'delivery_charge' => $totalAmount,
                'delivery_charge' => (float)$deliveryCharge,
                'shop_name' => $shop->name,
                'shop_logo' => $shop->logo,
                'shop_address' => $shop->districts->name . ', ' . $shop->states->name,
                'shop_rating' => (float) $shop->averageRating,
                'shop_online' => $lastOnline,
                'cash_on_delivery_enabled' => (bool) ($shop->cash_on_delivery_enabled ?? true),
                'online_payment_enabled' => (bool) ($shop->online_payment_enabled ?? false),
                'online_payment_provider' => $shop->online_payment_provider,
                'products' => $productArray,
            ];
        }

        return [
            'total_items' => $totalItems,
            'shop_wise_products' => $shopWiseProducts,
            'info' => $info,
        ];
    }

    /**
     * Store or update cart by request.
     */
    public static function storeOrUpdateByRequest(CartRequest $request, Product $product): Cart
    {
        $size = $request->size;
        $color = $request->color;
        $unit = $request->unit ?? $product->unit?->name;

        $isBuyNow = $request->is_buy_now ?? false;

        $customer = Auth::user()->customer;

        $cart = $customer->carts()
            ?->where('is_buy_now', $isBuyNow)
            ->where('product_id',  $request->product_id)
            ->where('variant_id',  $request->variant_id)
            ->where('bulk_item_id',  $request->bulk_item_id)
            ->first();

        if ($cart) {
            // CartRepository::syncvariant($cart, $request);
            $cart->update([
                'quantity' => $isBuyNow ?  $product->min_order_quantity ?? 1  : ($request->quantity ?? ($cart->quantity + 1)),
                'size' => $request->size ?? $cart->size,
                'color' => $request->color ?? $cart->color,
                'unit' => $request->unit ?? $cart->unit,
            ]);

            return $cart;
        }

        //here need logic to store CartVariant and CartBulkItem 

        // return self::create([
        //     'product_id' => $request->product_id,
        //     'shop_id' => $product->shop->id,
        //     'is_buy_now' => $isBuyNow,
        //     'customer_id' => $customer->id,
        //     'quantity' => $product->min_order_quantity ?? 1,
        //     'size' => $size,
        //     'color' => $color,
        //     'unit' => $unit,
        // ]);
        if ($request->filled('bulk_items')) {
            $selectedBulkIds = [];
            $cart = null;

            $existingBulkCarts = $customer->carts()
                ->where('is_buy_now', $isBuyNow)
                ->where('product_id', $product->id)
                ->whereNull('variant_id')
                ->whereNotNull('bulk_item_id')
                ->get()
                ->keyBy('bulk_item_id');

            foreach ($request->bulk_items as $bulkitem) {
                $bulkId = isset($bulkitem['id']) ? (int) $bulkitem['id'] : null;
                $requestQty = isset($bulkitem['buyqnty']) ? (int) $bulkitem['buyqnty'] : 0;

                if (! $bulkId || $requestQty < 1) {
                    continue;
                }

                $bulk = ProductBulkItem::find($bulkId);

                if (! $bulk) {
                    continue;
                }

                $qty = min($requestQty, (int) $bulk->quantity);

                if ($qty < 1) {
                    continue;
                }

                $selectedBulkIds[] = $bulk->id;

                $existing = $existingBulkCarts->get($bulk->id);

                if ($existing) {
                    $existing->update([
                        'quantity' => $qty,
                        'size' => $size,
                        'color' => $color,
                        'unit' => $unit,
                    ]);
                    $cart = $existing;
                } else {
                    $cart = self::create([
                        'product_id'  => $product->id,
                        'shop_id'     => $product->shop->id,
                        'is_buy_now'  => $isBuyNow,
                        'customer_id' => $customer->id,
                        'quantity'    => $qty,
                        'size'        => $size,
                        'color'       => $color,
                        'unit'        => $unit,
                        'variant_id' => null,
                        'bulk_item_id' => $bulk->id,
                    ]);
                }
            }

            $removeQuery = $customer->carts()
                ->where('is_buy_now', $isBuyNow)
                ->where('product_id', $product->id)
                ->whereNull('variant_id')
                ->whereNotNull('bulk_item_id');

            if (! empty($selectedBulkIds)) {
                $removeQuery->whereNotIn('bulk_item_id', $selectedBulkIds);
            }

            $removeQuery->delete();

            if ($cart) {
                return $cart;
            }

            return self::create([
                'product_id'  => $product->id,
                'shop_id'     => $product->shop->id,
                'is_buy_now'  => $isBuyNow,
                'customer_id' => $customer->id,
                'quantity'    => $product->min_order_quantity ?? 1,
                'size'        => $size,
                'color'       => $color,
                'unit'        => $unit,
                'variant_id' => null,
                'bulk_item_id' => null,
            ]);
        } else {
            $qty = $product->min_order_quantity ?? 1;
            $req_qty = $request->quantity ?? 1;
            if ($req_qty > $qty) {
                $qty = $req_qty;
            }
            $cart = self::create([
                'product_id'  => $product->id,
                'shop_id'     => $product->shop->id,
                'is_buy_now'  => $isBuyNow,
                'customer_id' => $customer->id,
                'quantity'    => $qty,
                'size'        => $size,
                'color'       => $color,
                'unit'        => $unit,
                'variant_id' => $request->variant_id,
                'bulk_item_id' => $request->bulk_item_id,
            ]);
        }

        // CartRepository::syncvariant($cart, $request);

        return $cart;
    }

    public static function syncvariant($cart, $request, $inc = true)
    {
        /**
         * ---------------------------
         * STORE CART VARIANT
         * ---------------------------
         */
        if ($request->filled('variant_id')) {

            $variant = ProductVariant::findOrFail($request->variant_id);

            $cartVariant = CartVariant::where('cart_id', $cart->id)
                ->where('product_variants_id', $variant->id)
                ->first();

            if ($cartVariant) {
                if ($inc) {
                    if ((int)$variant->quantity > (int)$cartVariant->quantity) {
                        $cartVariant->increment('quantity');
                    }
                } else {
                    $cartVariant->decrement('quantity');
                }
            } else {
                CartVariant::create([
                    'cart_id' => $cart->id,
                    'product_variants_id' => $variant->id,
                    'quantity' => 1,
                    'price' => $variant->price,
                ]);
            }
        }

        /**
         * ---------------------------
         * STORE CART BULK ITEMS
         * ---------------------------
         */
        if ($request->filled('bulk_items')) {

            foreach ($request->bulk_items as $bulkitem) {

                // ✅ Find only (no exception)
                $bulk = ProductBulkItem::find($bulkitem['id']);

                // ✅ Skip if bulk item not found
                if (! $bulk) {
                    continue;
                }

                $qty = (int) $bulkitem['buyqnty'];

                CartBulkItem::updateOrCreate(
                    [
                        'cart_id' => $cart->id,
                        'product_bulk_items_id' => $bulk->id,
                    ],
                    [
                        'quantity' => $qty,
                        'price' => $bulk->selling_price, // ✅ REQUIRED
                    ]
                );
            }
        }
    }

    public static function checkoutByRequest($request, $carts)
    {
        $totalAmount = 0;
        $deliveryCharge = 0;
        $couponDiscount = 0;
        $payableAmount = 0;

        $shop = null;

        $shopWiseTotalAmount = [];
        $totalOrderTaxAmount = 0;
        $vatTaxesArray = [];

        foreach ($carts ?? [] as $cart) {

            if (! $cart) {
                continue;
            }

            $product = $cart->product;
            $flashSale = $product->flashSales?->first();
            $flashSaleProduct = null;
            $quantity = null;

            $price = $product->discount_price > 0 ? $product->discount_price : $product->price;

            $falshPercentage = null;
            if ($flashSale) {
                $flashSaleProduct = $flashSale?->products()->where('id', $product->id)->first();

                $quantity = $flashSaleProduct?->pivot->quantity - $flashSaleProduct->pivot->sale_quantity;

                if ($quantity == 0) {
                    $quantity = null;
                    $flashSaleProduct = null;
                } else {
                    $falshPercentage = $flashSale?->pivot->discount;
                    // $price = $flashSaleProduct->pivot->price;
                }
            }


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

            if ($falshPercentage) {
                $price = $price - ($price * ($falshPercentage / 100));
            }

            $sizePrice = $product->sizes()?->where('id', $cart->size)->first()?->pivot?->price ?? 0;
            $price = $price + $sizePrice;

            $colorPrice = $product->colors()?->where('id', $cart->color)->first()?->pivot?->price ?? 0;
            $price = $price + $colorPrice;

            // get shop wise total amount
            $shop = $product->shop;
            if (array_key_exists($shop->id, $shopWiseTotalAmount)) {
                $currentAmount = $shopWiseTotalAmount[$shop->id];
                $shopWiseTotalAmount[$shop->id] = $currentAmount + ($price * $cart->quantity);
            } else {
                $shopWiseTotalAmount[$shop->id] = $price * $cart->quantity;
            }

            $totalAmount += $price * $cart->quantity;
        }

        $groupCarts = $carts->groupBy('shop_id');

        // get delivery charge
        // $deliveryCharge = 0;
        // foreach ($groupCarts as $shopId => $shopCarts) {

        //     $productQty = 0;

        //     foreach ($shopCarts as $cart) {
        //         $productQty += $cart->quantity;
        //     }

        //     if ($productQty > 0) {
        //         $deliveryCharge += getDeliveryCharge($productQty);
        //     }
        // }

        $stateId = self::resolveStateIdFromRequest($request);
        $isDeliverable = true;
        $deliveryCharge = getShopDeliveryCharge($totalAmount, $shop, $stateId);
        if ($deliveryCharge === null) {
            $isDeliverable = false;
            $deliveryCharge = 0.00;
        }

        // generate array for get discount
        $products = collect([]);
        foreach ($carts as $cart) {
            $products->push([
                'id' => $cart->product_id,
                'quantity' => (int) $cart->quantity,
                'shop_id' => $cart->shop_id,
            ]);
        }

        $couponDiscount = 0.00;
        if (request()->filled('coupon_code')) {
            $array = (object) [
                'coupon_code' => request()->coupon_code ?? '',
                'products' => $products,
            ];

            // get coupon discount
            $getDiscount = CouponRepository::getCouponDiscount($array);

            $couponDiscount = $getDiscount['discount_amount'];
        }

        $payableAmount = $totalAmount + $deliveryCharge - $couponDiscount;

        // get order base tax
        $vatTaxes = VatTaxRepository::getActiveVatTaxes();

        foreach ($shopWiseTotalAmount as $shopId => $subtotal) {

            $thisFinalTax = [];

            foreach ($vatTaxes as $vatTax) {
                if ($vatTax->name && $vatTax->percentage > 0) {

                    $totalTaxAmount = round($subtotal * ($vatTax->percentage / 100), 2);

                    if (array_key_exists($vatTax->id, $thisFinalTax)) {
                        $currentAmount = $thisFinalTax[$vatTax->id];
                        $thisFinalTax[$vatTax->id] = $currentAmount + $totalTaxAmount;
                    } else {
                        $thisFinalTax[$vatTax->id] = $totalTaxAmount;
                    }
                    $totalOrderTaxAmount += $totalTaxAmount;
                }
            }

            $vatTaxesArray = $vatTaxes->map(function ($vatTax) use ($thisFinalTax) {
                return [
                    'id' => $vatTax->id,
                    'name' => $vatTax->name,
                    'percentage' => $vatTax->percentage,
                    'amount' => $thisFinalTax[$vatTax->id] ?? 0,
                ];
            })->toArray();
        }

        $payableAmount += $totalOrderTaxAmount;

        // $setting = DeliverySetting::where('shop_id', $shop?->id)->first();

        return [
            'is_deliverable' => $isDeliverable,
            'total_amount' => (float) round($totalAmount, 2),
            'delivery_charge' => (float) round($deliveryCharge, 2),
            'coupon_discount' => (float) round($couponDiscount, 2),
            'order_tax_amount' => (float) round($totalOrderTaxAmount, 2),
            'payable_amount' => (float) round($payableAmount, 2),
            'all_vat_taxes' => $vatTaxesArray,
        ];
    }
}

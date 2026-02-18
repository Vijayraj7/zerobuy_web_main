<?php

namespace App\Http\Controllers\API\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\Seller\FlashSaleProductResource;
use App\Http\Resources\Seller\FlashSaleResource;
use App\Http\Resources\SellerProductResource;
use App\Models\FlashSale;
use App\Models\Product;
use Illuminate\Http\Request;

class FlashSaleController extends Controller
{
    public function index()
    {
        $flashSales = FlashSale::latest('id')->get();

        return $this->json('flash sales list', [
            'flash_sales' => FlashSaleResource::collection($flashSales),
        ]);
    }

    public function show(FlashSale $flashSale)
    {
        $shop = generaleSetting('shop');

        $dealProducts = $flashSale->products()
            ->where('shop_id', $shop->id)
            ->get();

        $availableProducts = $shop->products()
            ->whereNotIn('id', $dealProducts->pluck('id'))
            ->isActive()
            ->get();

        return $this->json('flash sale details', [
            'flash_sale' => FlashSaleResource::make($flashSale),
            'deal_products' => FlashSaleProductResource::collection($dealProducts),
            'available_products' => SellerProductResource::collection($availableProducts),
        ]);
    }

    public function productStore(FlashSale $flashSale, Request $request)
    {
        $shop = generaleSetting('shop');
        $items = $request->input('products');

        if (empty($items)) {
            $items = [[
                'id' => $request->input('product_id'),
                'price' => $request->input('price'),
                'quantity' => $request->input('quantity'),
            ]];
        }

        $errors = [];
        foreach ($items as $item) {
            $productId = $item['id'] ?? null;
            $price = $item['price'] ?? null;
            $quantity = $item['quantity'] ?? null;

            if (! $productId || ! $price || ! $quantity) {
                $errors[] = [
                    'product_id' => $productId,
                    'message' => 'Missing product_id, price, or quantity.',
                ];
                continue;
            }

            $product = Product::where('shop_id', $shop->id)->find($productId);
            if (! $product) {
                $errors[] = [
                    'product_id' => $productId,
                    'message' => 'Product not found for this shop.',
                ];
                continue;
            }

            $productPrice = $product->discount_price > 0
                ? $product->discount_price
                : $product->price;

            if ($price >= $productPrice) {
                $errors[] = [
                    'product_id' => $productId,
                    'message' => 'Flash sale price must be less than product price.',
                ];
                continue;
            }

            $productQty = $quantity < $product->quantity ? $quantity : $product->quantity;
            $discountPercentage = ($productPrice - $price) / $productPrice * 100;

            if ($flashSale->products()->where('product_id', $productId)->exists()) {
                $flashSale->products()->updateExistingPivot($productId, [
                    'price' => $price,
                    'quantity' => $productQty,
                    'discount' => $discountPercentage,
                ]);
                continue;
            }

            $flashSale->products()->attach($productId, [
                'price' => $price,
                'quantity' => $productQty,
                'discount' => $discountPercentage,
            ]);
        }

        return $this->json('product store completed', [
            'errors' => $errors,
        ]);
    }

    public function productRemove(FlashSale $flashSale, Product $product)
    {
        $flashSale->products()->detach($product->id);

        return $this->json('product removed', []);
    }

    public function update(FlashSale $flashSale, Product $product, Request $request)
    {
        $shop = generaleSetting('shop');

        if ($product->shop_id !== $shop->id) {
            return $this->json('Product does not belong to this shop.', [], 403);
        }

        $price = $request->input('price');
        $quantity = $request->input('quantity');

        if ($price === null || $quantity === null) {
            return $this->json('Price and quantity are required.', [], 422);
        }

        $productPrice = $product->discount_price > 0
            ? $product->discount_price
            : $product->price;

        if ($price >= $productPrice) {
            return $this->json('Flash sale price must be less than product price.', [], 422);
        }

        if ($quantity > $product->quantity) {
            return $this->json('Quantity cannot be greater than product quantity.', [], 422);
        }

        $discountPercentage = ($productPrice - $price) / $productPrice * 100;

        $flashSale->products()->updateExistingPivot($product->id, [
            'price' => $price,
            'quantity' => $quantity,
            'discount' => $discountPercentage,
        ]);

        return $this->json('updated successfully', []);
    }
}

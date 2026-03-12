<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\Product;
use Illuminate\Http\Request;

class FlashSaleController extends Controller
{
    public function index()
    {
        $shop = generaleSetting('shop');
        $categoryIds = $shop->businessCategories()->pluck('business_categories.id');

        $flashSales = FlashSale::with('businessCategory:id,name')
            ->where(function ($query) use ($categoryIds) {
                $query->whereNull('business_category_id')
                    ->orWhereIn('business_category_id', $categoryIds);
            })
            ->latest('id')
            ->paginate(20);

        return view('shop.flashsale.index', compact('flashSales'));
    }

    public function show(FlashSale $flashSale)
    {
        $flashSale->load('businessCategory:id,name');

        $shop = generaleSetting('shop');

        $dealProducts = $flashSale->products()->where('shop_id', $shop->id)->get();

        $products = $shop->products()
            ->whereNotIn('id', $dealProducts->pluck('id'))
            ->isActive()
            ->when($flashSale->business_category_id, function ($query) use ($flashSale) {
                $query->whereHas('categories', function ($categoryQuery) use ($flashSale) {
                    $categoryQuery->where('business_category_id', $flashSale->business_category_id);
                });
            })
            ->get();

        return view('shop.flashsale.show', compact('flashSale', 'products', 'dealProducts'));
    }

    public function productStore(FlashSale $flashSale, Request $request)
    {
        $hasAnyErrors = [];

        foreach ($request->products as $productArr) {

            $product = Product::find($productArr['id']);

            if ($product) {
                if (! $this->isProductAllowedForFlashSale($flashSale, $product)) {
                    $hasAnyErrors[] = $product;
                    continue;
                }

                $productPrice = $product->discount_price > 0 ? $product->discount_price : $product->price;

                $flashDiscountPercentage = (float) $flashSale->discount;
                $flashPrice = (float) number_format(
                    $productPrice - ($productPrice * ($flashDiscountPercentage / 100)),
                    2,
                    '.',
                    ''
                );

                $productQty = $productArr['quantity'] < $product->quantity ? $productArr['quantity'] : $product->quantity;

                if ($productPrice >= $flashPrice) {
                    $flashSale->products()->attach($productArr['id'], [
                        'price' => $flashPrice,
                        'quantity' => $productQty,
                        'discount' => $flashDiscountPercentage,
                    ]);
                } else {
                    $hasAnyErrors[] = $product;
                }
            }
        }

        return back()->withSuccess(__('Product added successfully'))->with('hasAnyErrors', $hasAnyErrors);
    }

    public function productRemove(FlashSale $flashSale, Product $product)
    {
        $flashSale->products()->detach($product->id);

        return back()->withSuccess(__('Product removed successfully'));
    }

    public function update(FlashSale $flashSale, Product $product, Request $request)
    {
        if (! $this->isProductAllowedForFlashSale($flashSale, $product)) {
            return back()->withError(__('Selected product does not belong to this flash sale business category.'));
        }

        $productPrice = $product->discount_price > 0 ? $product->discount_price : $product->price;

        $flashDiscountPercentage = (float) $flashSale->discount;
        $flashPrice = (float) number_format(
            $productPrice - ($productPrice * ($flashDiscountPercentage / 100)),
            2,
            '.',
            ''
        );

        if ($productPrice <= $flashPrice) {
            return back()->withError(__('Flash sale price cannot be greater or equal than product price!'));
        }

        if ($request->quantity > $product->quantity) {
            return back()->withError(__('Quantity cannot be greater than product quantity!'));
        }

        $flashSale->products()->updateExistingPivot($product->id, [
            'price' => $flashPrice,
            'quantity' => $request->quantity,
            'discount' => $flashDiscountPercentage,
        ]);

        return back()->withSuccess(__('Updated Successfully'));
    }

    private function isProductAllowedForFlashSale(FlashSale $flashSale, Product $product): bool
    {
        if (! $flashSale->business_category_id) {
            return true;
        }

        return $product->categories()
            ->where('business_category_id', $flashSale->business_category_id)
            ->exists();
    }
}

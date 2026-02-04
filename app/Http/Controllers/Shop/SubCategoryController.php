<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\SubCategory; 
use App\Models\BusinessCategory; 
use Illuminate\Http\Request;

class SubCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     $shop = generaleSetting('rootShop');

    //     $subCategories = $shop->subCategories()->latest('id')->paginate(10);

    //     return view('shop.sub-category.index', compact('subCategories'));
    // }

    public function index(Request $request)
    {
        $shop = generaleSetting('shop');
        $shopId = $shop->id;

        $sortBy    = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');

        // $query = SubCategory::with([
        //     'businessCategory',
        //     'category'
        // ]);
        $query = SubCategory::withCount([
            'products as products_count' => function ($q) use ($shopId) {
                $q->where('shop_id', $shopId);
            }
        ])->with([
            'businessCategory',
            'category'
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
            ->orWhereHas('category', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhereHas('businessCategory', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                }); 
            });
        }
        // $subCategories = $query->paginate(50);
        $subCategories = $query->sortByField($sortBy, $sortOrder)->paginate(50)->withQueryString();

        $businessCategories = BusinessCategory::active()->get();

        return view('shop.sub-category.index', compact(
            'subCategories',
            'businessCategories',
            'sortBy', 'sortOrder'
        ));
    }
}

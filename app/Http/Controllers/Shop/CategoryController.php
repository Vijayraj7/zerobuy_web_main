<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;  
use App\Models\BusinessCategory;

class CategoryController extends Controller
{
    /**
     * Display a category listing.
     */
    // public function index(Request $request)
    // {
    //     $search = $request->search ?? null;

    //     $shop = generaleSetting('rootShop');

    //     // Get categories with search and pagination
    //     $categories = $shop->categories()->when($search, function ($query) use ($search) {
    //         return $query->where('name', 'like', '%'.$search.'%');
    //     })->paginate(20);

    //     return view('shop.category.index', compact('categories'));
    // }

    public function index(Request $request)
    {
        $sortBy    = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');

        $query = Category::with(['businessCategory']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
            ->orWhereHas('businessCategory', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        // $Categories = $query->paginate(50);
        $Categories = $query->sortByField($sortBy, $sortOrder)->paginate(50)->withQueryString();

        $businessCategories = BusinessCategory::active()->get();

        return view('shop.category.index', compact(
            'Categories',
            'businessCategories',
            'sortBy', 'sortOrder'
        )); 
    }
}

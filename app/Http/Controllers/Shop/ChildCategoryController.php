<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChildCategory; 
use App\Models\BusinessCategory; 
use App\Repositories\MediaRepository; 

class ChildCategoryController extends Controller
{
    public function index(Request $request)
    {
        $sortBy    = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');

        $query = ChildCategory::with([
            'businessCategory',
            'category',
            'subCategory'
        ]);
        // ->latest('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
            ->orWhereHas('subCategory', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhereHas('category', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                    ->orWhereHas('businessCategory', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
                }); 
            });
        } 

        $childCategories = $query->sortByField($sortBy, $sortOrder)->paginate(50)->withQueryString();

        $businessCategories = BusinessCategory::active()->get();

        return view('shop.child-category.index', compact(
            'childCategories',
            'businessCategories', 
            'sortBy', 'sortOrder'
        ));
    }
}

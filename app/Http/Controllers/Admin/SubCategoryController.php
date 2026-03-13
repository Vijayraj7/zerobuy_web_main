<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use App\Models\BusinessCategory;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Repositories\MediaRepository; 

class SubCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sortBy    = $request->input('sort_by');
        $sortOrder = $request->input('sort_order', 'asc');
        $businessCategoryId = $request->input('business_category_id');
        $categoryId = $request->input('category_id');

        $query = SubCategory::withCount([
            'products' 
        ])->with([
            'businessCategory',
            'category'
        ]);

        if ($businessCategoryId) {
            $query->where('business_category_id', $businessCategoryId);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhereHas('businessCategory', function ($q3) use ($search) {
                                $q3->where('name', 'like', "%{$search}%");
                            });
                    });
            });
        }
        if ($sortBy) {
            $query->sortByField($sortBy, $sortOrder);
        }
        $subCategories = $query->paginate(50)->withQueryString();

        $businessCategories = BusinessCategory::active()->get();
        $categories = $businessCategoryId
            ? Category::active()->where('business_category_id', $businessCategoryId)->get(['id', 'name'])
            : collect();

        return view('admin.sub-category.index', compact(
            'subCategories',
            'businessCategories',
            'categories',
            'sortBy',
            'sortOrder',
            'businessCategoryId',
            'categoryId'
        ));
    }

    public function store(Request $request)
    { 
        $shop = generaleSetting('rootShop');

        $this->validate($request, [
            'business_category_id' => 'required',
            'category_id' => 'required', 
            'name' => 'required',
            'thumbnail' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
        ]);

        try {
            if ($request->hasFile('thumbnail')) {   // Store thumbnail if exists
                $thumbnail = MediaRepository::storeByRequest(
                    $request->file('thumbnail'),
                    'categories',
                    'image'
                );
            }
            SubCategory::create([
                'business_category_id'  => $request->business_category_id, 
                'category_id'           => $request->category_id, 
                'shop_id'               => $shop->id,
                'name'                  => $request->name,
                'slug'                  => Str::slug($request->name, '-'),
                'media_id'              => $thumbnail->id ?? null,
                'is_active'             => true,
                'sort_order'            => (SubCategory::withoutGlobalScope('sorted')->max('sort_order') ?? 0) + 1,
            ]);
            return response()->json(['message' => 'New Sub Category Created Successfully']);
        }catch (\Exception $e) { 
            return back()->with('error','somethingwrong');
        }
    }

    public function edit(SubCategory $subCategory)
    {
        // Fetch categories and subcategories for this business category
        $categories = Category::where('business_category_id', $subCategory->business_category_id)->where('status', 1)->select('id', 'name')->get(); 

        return response()->json([
            'subCategory' => $subCategory,
            'categories'    => $categories, 
            'thumbnail'     => $subCategory->thumbnail,
        ]);
    }

    public function update(Request $request, SubCategory $subCategory)
    {
        $this->validate($request, [
            'business_category_id' => 'required',
            'category_id'          => 'required', 
            'name'                 => 'required',
            'thumbnail'            => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
        ]);

        try {
            if ($request->hasFile('thumbnail')) {
                $thumbnail = MediaRepository::storeByRequest(
                    $request->file('thumbnail'),
                    'categories',
                    'image'
                );
                $subCategory->media_id = $thumbnail->id;
            }

            $subCategory->update([
                'business_category_id' => $request->business_category_id,
                'category_id'          => $request->category_id, 
                'name'                 => $request->name,
                'slug'                 => Str::slug($request->name, '-'),
            ]);
            return response()->json(['message' => 'Sub Category Updated Successfully']);
        }catch (\Exception $e) { 
            return back()->with('error','somethingwrong');
        }
    }

    public function statusToggle(SubCategory $subCategory)
    {
        $subCategory->update([
            'is_active' => ! $subCategory->is_active
        ]);
        return response()->json(['success' => true]);
    }

    public function reorder(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer', 'base' => 'nullable|integer|min:1']);
        $base = (int) ($request->input('base') ?: 1);
        foreach ($request->ids as $order => $id) {
            SubCategory::withoutGlobalScope('sorted')->where('id', $id)->update(['sort_order' => $base + $order]);
        }
        return response()->json(['message' => 'Order saved']);
    }

    public function reorderAlphabetic()
    {
        $ids = SubCategory::withoutGlobalScope('sorted')
            ->orderBy('name', 'asc')
            ->pluck('id');

        foreach ($ids as $order => $id) {
            SubCategory::withoutGlobalScope('sorted')
                ->where('id', $id)
                ->update(['sort_order' => $order + 1]);
        }

        return response()->json(['message' => 'Alphabetic order saved']);
    }
}
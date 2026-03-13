<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BusinessCategory;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\ChildCategory; 
use Illuminate\Support\Str;
use App\Repositories\MediaRepository; 

class ChildCategoryController extends Controller
{
    public function index(Request $request)
    {
        $sortBy    = $request->input('sort_by');
        $sortOrder = $request->input('sort_order', 'asc');
        $businessCategoryId = $request->input('business_category_id');
        $categoryId = $request->input('category_id');
        $subCategoryId = $request->input('sub_category_id');

        $query = ChildCategory::with([
            'businessCategory',
            'category',
            'subCategory'
        ])->withCount('products');
        // ->latest('id');

        if ($businessCategoryId) {
            $query->where('business_category_id', $businessCategoryId);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($subCategoryId) {
            $query->where('sub_category_id', $subCategoryId);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('subCategory', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhereHas('category', function ($q3) use ($search) {
                                $q3->where('name', 'like', "%{$search}%")
                                    ->orWhereHas('businessCategory', function ($q4) use ($search) {
                                        $q4->where('name', 'like', "%{$search}%");
                                    });
                            });
                    });
            });
        } 

        if ($sortBy) {
            $query->sortByField($sortBy, $sortOrder);
        }
        $childCategories = $query->paginate(50)->withQueryString();

        $businessCategories = BusinessCategory::active()->get();
        $categories = $businessCategoryId
            ? Category::active()->where('business_category_id', $businessCategoryId)->get(['id', 'name'])
            : collect();
        $subCategories = $categoryId
            ? SubCategory::isActive()->where('category_id', $categoryId)->get(['id', 'name'])
            : collect();

        return view('admin.child-category.index', compact(
            'childCategories',
            'businessCategories', 
            'categories',
            'subCategories',
            'sortBy',
            'sortOrder',
            'businessCategoryId',
            'categoryId',
            'subCategoryId'
        ));
    }

    public function store(Request $request)
    { 
        $this->validate($request, [
            'business_category_id' => 'required',
            'category_id' => 'required',
            'sub_category_id' => 'required',
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
            ChildCategory::create([
                'business_category_id'  => $request->business_category_id, 
                'category_id'           => $request->category_id,
                'sub_category_id'       => $request->sub_category_id,
                'name'                  => $request->name,
                'slug'                  => Str::slug($request->name, '-'),
                'media_id'              => $thumbnail->id ?? null,
                'status'                => true,
                'sort_order'            => (ChildCategory::withoutGlobalScope('sorted')->max('sort_order') ?? 0) + 1,
            ]);
            return response()->json(['message' => 'New Child Category Created Successfully']);
        }catch (\Exception $e) { 
            return back()->with('error','somethingwrong');
        }
    }

    public function edit(ChildCategory $childCategory)
    {
        // Fetch categories and subcategories for this business category
        $categories = Category::where('business_category_id', $childCategory->business_category_id)->where('status', 1)->select('id', 'name')->get();
        $subCategories = SubCategory::where('category_id', $childCategory->category_id)->where('is_active', 1)->select('id', 'name')->get();

        return response()->json([
            'childCategory' => $childCategory,
            'categories'    => $categories,
            'subCategories' => $subCategories,
            'thumbnail'     => $childCategory->thumbnail,
        ]);
    }

    public function update(Request $request, ChildCategory $childCategory)
    {
        $this->validate($request, [
            'business_category_id' => 'required',
            'category_id'          => 'required',
            'sub_category_id'      => 'required',
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
                $childCategory->media_id = $thumbnail->id;
            }

            $childCategory->update([
                'business_category_id' => $request->business_category_id,
                'category_id'          => $request->category_id,
                'sub_category_id'      => $request->sub_category_id,
                'name'                 => $request->name,
                'slug'                 => Str::slug($request->name, '-'),
            ]);
            return response()->json(['message' => 'Child Category Updated Successfully']);
        }catch (\Exception $e) { 
            return back()->with('error','somethingwrong');
        }
    }

    public function statusToggle(ChildCategory $childCategory)
    {
        $childCategory->update([
            'status' => ! $childCategory->status
        ]);
        return response()->json(['success' => true]);
    }

    public function reorder(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer', 'base' => 'nullable|integer|min:1']);
        $base = (int) ($request->input('base') ?: 1);
        foreach ($request->ids as $order => $id) {
            ChildCategory::withoutGlobalScope('sorted')->where('id', $id)->update(['sort_order' => $base + $order]);
        }
        return response()->json(['message' => 'Order saved']);
    }

    public function reorderAlphabetic()
    {
        $ids = ChildCategory::withoutGlobalScope('sorted')
            ->orderBy('name', 'asc')
            ->pluck('id');

        foreach ($ids as $order => $id) {
            ChildCategory::withoutGlobalScope('sorted')
                ->where('id', $id)
                ->update(['sort_order' => $order + 1]);
        }

        return response()->json(['message' => 'Alphabetic order saved']);
    }
}
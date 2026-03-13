<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller; 
use App\Models\Category; 
use Illuminate\Http\Request;
use App\Models\BusinessCategory;
use Illuminate\Support\Str;
use App\Repositories\MediaRepository;  

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $sortBy    = $request->input('sort_by');
        $sortOrder = $request->input('sort_order', 'asc');
        $businessCategoryId = $request->input('business_category_id');

        $query = Category::with(['businessCategory'])->withCount([ 'products' ]);

        if ($businessCategoryId) {
            $query->where('business_category_id', $businessCategoryId);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('businessCategory', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }
        if ($sortBy) {
            $query->sortByField($sortBy, $sortOrder);
        }
        $Categories = $query->paginate(50)->withQueryString();

        $businessCategories = BusinessCategory::active()->get();

        return view('admin.category.index', compact(
            'Categories',
            'businessCategories',
            'sortBy',
            'sortOrder',
            'businessCategoryId'
        )); 
    }
    /**
     * create a new category
     */
    public function create()
    {
        // return view('admin.category.create');
        $businessCategories = BusinessCategory::active()->get();
        return view('admin.category.create', compact('businessCategories'));
    }

    /**
     * store a new category
     */
    public function store(Request $request)
    { 
        $this->validate($request, [
            'business_category_id' => 'required',  
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
            Category::create([
                'business_category_id'  => $request->business_category_id,  
                'name'                  => $request->name, 
                'media_id'              => $thumbnail->id ?? null,
                'status'                => true,
                'sort_order'            => (Category::withoutGlobalScope('sorted')->max('sort_order') ?? 0) + 1,
            ]);
            return response()->json(['message' => 'New Category Created Successfully']);
        }catch (\Exception $e) { 
            return back()->with('error','somethingwrong');
        }
    }

    /**
     * edit a category
     */
    public function edit(Category $category)
    {
        return response()->json([
            'id'                        => $category->id,
            'name'                      => $category->name,
            'business_category_id'      => $category->business_category_id, 
            'thumbnail'                 => $category->thumbnail,
        ]);
    }

    /**
     * update a category
     */ 
    public function update(Request $request, Category $category)
    {
        $this->validate($request, [
            'business_category_id' => 'required', 
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
                $category->media_id = $thumbnail->id;
            }

            $category->update([
                'business_category_id' => $request->business_category_id, 
                'name'                 => $request->name, 
            ]);
            return response()->json(['message' => 'Category Updated Successfully']);
        }catch (\Exception $e) { 
            return back()->with('error','somethingwrong');
        }
    }

    /**
     * category status toggle
     */ 
    public function statusToggle(Category $category)
    {
        $category->update([
            'status' => ! $category->status
        ]);
        return response()->json(['success' => true]);
    }

    public function reorder(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer', 'base' => 'nullable|integer|min:1']);
        $base = (int) ($request->input('base') ?: 1);
        foreach ($request->ids as $order => $id) {
            Category::withoutGlobalScope('sorted')->where('id', $id)->update(['sort_order' => $base + $order]);
        }
        return response()->json(['message' => 'Order saved']);
    }

    public function reorderAlphabetic()
    {
        $ids = Category::withoutGlobalScope('sorted')
            ->orderBy('name', 'asc')
            ->pluck('id');

        foreach ($ids as $order => $id) {
            Category::withoutGlobalScope('sorted')
                ->where('id', $id)
                ->update(['sort_order' => $order + 1]);
        }

        return response()->json(['message' => 'Alphabetic order saved']);
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Requests\BusinessCategoryRequest;
use App\Models\BusinessCategory;
use App\Repositories\MediaRepository;

class BusinessCategoryController extends Controller
{
    public function index(Request $request)
    {
        $sortBy    = $request->input('sort_by');
        $sortOrder = $request->input('sort_order', 'asc');
        $allowedSortFields = ['id', 'name', 'status'];
        if ($sortBy && !in_array($sortBy, $allowedSortFields)) {
            $sortBy = null;
        }

        $query = BusinessCategory::withCount([
            'categories as category_products_count' => function ($q) {
                $q->join('product_categories', 'categories.id', '=', 'product_categories.category_id');
            } 
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($sortBy) {
            $query->orderBy($sortBy, $sortOrder);
        }
        $categories = $query->paginate(50)->withQueryString();

        return view('admin.business-category.index', compact('categories','sortBy', 'sortOrder'));  
    }

    public function store(BusinessCategoryRequest $request)
    {
        if ($request->hasFile('thumbnail')) {   // Store thumbnail if exists
            $thumbnail = MediaRepository::storeByRequest(
                $request->file('thumbnail'),
                'categories',
                'image'
            );
        }

        BusinessCategory::create([
            'name'          => $request->name,
            'slug'          => Str::slug($request->name, '-'),
            'media_id'      => $thumbnail->id ?? null,
            'status'        => 1,
            'sort_order'    => (BusinessCategory::withoutGlobalScope('sorted')->max('sort_order') ?? 0) + 1,
        ]);
        return response()->json(['message' => 'New Business category created successfully']); 
    }

    public function edit(BusinessCategory $businessCategory)
    {
        return response()->json([
            'id'                        => $businessCategory->id,
            'name'                      => $businessCategory->name,  
            'thumbnail'                 => $businessCategory->thumbnail,
        ]);
    }

    public function update(Request $request, BusinessCategory $businessCategory) 
    {
        $this->validate($request, [ 
            'name'                 => 'required',
            'thumbnail'            => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
        ]);

        if ($request->hasFile('thumbnail')) {
            $thumbnail = MediaRepository::storeByRequest(
                $request->file('thumbnail'),
                'categories',
                'image'
            );
            $businessCategory->media_id = $thumbnail->id;
        }

        $businessCategory->update([
            'name' => $request->name
        ]); 

        return response()->json(['message' => 'Business category updated successfully']);
    }

    public function statusToggle(BusinessCategory $businessCategory)
    {
        $businessCategory->update([
            'status' => ! $businessCategory->status
        ]);
        return response()->json(['success' => true]);
    }

    public function reorder(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer', 'base' => 'nullable|integer|min:1']);
        $base = (int) ($request->input('base') ?: 1);
        foreach ($request->ids as $order => $id) {
            BusinessCategory::withoutGlobalScope('sorted')->where('id', $id)->update(['sort_order' => $base + $order]);
        }
        return response()->json(['message' => 'Order saved']);
    }

    public function reorderAlphabetic()
    {
        $ids = BusinessCategory::withoutGlobalScope('sorted')
            ->orderBy('name', 'asc')
            ->pluck('id');

        foreach ($ids as $order => $id) {
            BusinessCategory::withoutGlobalScope('sorted')
                ->where('id', $id)
                ->update(['sort_order' => $order + 1]);
        }

        return response()->json(['message' => 'Alphabetic order saved']);
    }
}
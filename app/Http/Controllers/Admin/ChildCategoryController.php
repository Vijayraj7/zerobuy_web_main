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
        $sortBy    = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');

        $query = ChildCategory::with([
            'businessCategory',
            'category',
            'subCategory'
        ])->withCount('products');
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

        return view('admin.child-category.index', compact(
            'childCategories',
            'businessCategories', 
            'sortBy', 'sortOrder'
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
}
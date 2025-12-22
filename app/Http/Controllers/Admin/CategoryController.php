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
        $query = Category::with(['businessCategory'])->latest('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
            ->orWhereHas('businessCategory', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        $Categories = $query->paginate(10);

        $businessCategories = BusinessCategory::active()->get();

        return view('admin.category.index', compact(
            'Categories',
            'businessCategories'
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
}

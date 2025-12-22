<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Requests\BusinessCategoryRequest;
use App\Models\BusinessCategory;

class BusinessCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = BusinessCategory::latest('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $categories = $query->paginate(10); 

        return view('admin.business-category.index', compact('categories'));  
    }

    public function store(BusinessCategoryRequest $request)
    {
        BusinessCategory::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name, '-'),
            'status' => 1,
        ]);

        return back()->withSuccess('Business category created successfully');
    }

    public function update(BusinessCategoryRequest $request, BusinessCategory $businessCategory) 
    {
        $businessCategory->update([
            'name' => $request->name
        ]);

        return back()->withSuccess('Business category updated successfully');
    }

    public function statusToggle(BusinessCategory $businessCategory)
    {
        $businessCategory->update([
            'status' => ! $businessCategory->status
        ]);

        return response()->json(['success' => true]);
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Requests\BusinessCategoryRequest;
use App\Http\Resources\BusinessCategoryResource;
use App\Models\BusinessCategory;
use App\Repositories\BusinessCategoryRepository;
use App\Repositories\MediaRepository;

class BusinessCategoryController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->page;
        $perPage = $request->per_page;
        $skip = ($page * $perPage) - $perPage;

        $shop = generaleSetting('rootShop');

        $categories = BusinessCategoryRepository::query()->active()
            // ->whereHas('shops', function ($query) use ($shop) {
            //     $query->where('id', $shop->id);
            // })->whereHas('products', function ($query) {
            //     $query->whereHas('shop', function ($query) {
            //         return $query->isActive();
            //     });
            // })
            ->latest('id');

        $total = $categories->count();

        $categories = $categories->when($perPage && $page, function ($query) use ($perPage, $skip) {
            return $query->skip($skip)->take($perPage);
        })->with('categories')->get();

        return $this->json('categories', [
            'total' => $total,
            'categories' => BusinessCategoryResource::collection($categories),
        ]);
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
}

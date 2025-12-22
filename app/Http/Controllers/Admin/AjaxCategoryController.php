<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Support\Facades\Cache;

class AjaxCategoryController extends Controller
{
    public function categories($businessCategoryId)
    {
        // return Cache::remember(
        //     "categories_$businessCategoryId",
        //     3600,
        //     fn () => Category::select('id','name')
        //         ->where('business_category_id', $businessCategoryId)
        //         ->where('status', 1)
        //         ->get()
        // );
        return  Category::select('id', 'name')
            ->where('business_category_id', $businessCategoryId)
            ->where('status', 1)
            ->get();
    }

    public function subCategories($categoryId)
    {
        // return Cache::remember(
        //     "subcategories_$categoryId",
        //     3600,
        //     fn () => SubCategory::select('id','name')
        //         ->where('category_id', $categoryId)
        //         ->where('is_active', 1)
        //         ->get()
        // );
        return SubCategory::select('id', 'name')
            ->where('category_id', $categoryId)
            ->where('is_active', 1)
            ->get();
    }
}

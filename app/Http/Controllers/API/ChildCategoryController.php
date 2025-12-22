<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChildCategory;

class ChildCategoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'sub_category_id' => 'required|exists:sub_categories,id',
        ]);

        $childCategories = ChildCategory::where('sub_category_id', $request->sub_category_id)
            ->where('status', 1)
            ->get(['id', 'name']);

        return response()->json([
            'status' => true,
            'data' => $childCategories,
        ]);
    }
}

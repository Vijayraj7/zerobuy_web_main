<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * Get AI-powered search suggestions based on query
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSearchSuggestions(Request $request)
    {
        $query = $request->input('query', '');
        $businessCategoryId = $request->input('business_category_id');
        
        if (empty($query)) {
            return $this->json('Query is required', [], Response::HTTP_BAD_REQUEST);
        }

        $suggestions = [];
        
        // Search in products
        $productsQuery = Product::where('name', 'LIKE', "%{$query}%")
            ->isActive()
            ->with(['categories', 'subcategories']);
            
        if ($businessCategoryId) {
            // Filter products through their categories
            $productsQuery->whereHas('categories', function ($q) use ($businessCategoryId) {
                $q->where('business_category_id', $businessCategoryId);
            });
        }
        
        $products = $productsQuery->limit(5)->get();

        foreach ($products as $product) {
            // Get first category and subcategory (products can have multiple)
            $firstCategory = $product->categories->first();
            $firstSubCategory = $product->subcategories->first();
            
            $suggestions[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'category_id' => $firstCategory?->id,
                'category_name' => $firstCategory?->name,
                'sub_category_id' => $firstSubCategory?->id,
                'sub_category_name' => $firstSubCategory?->name,
                'sort_type' => null,
                'sub_categories' => [],
                'thumbnail' => $product->thumbnail,
                'business_category_name' => null,
            ];
        }

        // Search in categories
        $categoriesQuery = Category::where('name', 'LIKE', "%{$query}%")
            ->active()
            ->with(['subCategories', 'businessCategory']);
            
        if ($businessCategoryId) {
            $categoriesQuery->where('business_category_id', $businessCategoryId);
        }
        
        $categories = $categoriesQuery->limit(3)->get();

        foreach ($categories as $category) {
            $subCategoriesArray = $category->subCategories->map(function ($subCat) {
                return [
                    'id' => $subCat->id,
                    'name' => $subCat->name,
                ];
            })->toArray();

            $suggestions[] = [
                'product_name' => null,
                'category_id' => $category->id,
                'category_name' => $category->name,
                'sub_category_id' => null,
                'sub_category_name' => null,
                'sort_type' => null,
                'sub_categories' => $subCategoriesArray,
                'thumbnail' => $category->thumbnail,
                'business_category_name' => $category->businessCategory?->name,
            ];
        }

        // Search in subcategories
        $subCategoriesQuery = SubCategory::where('name', 'LIKE', "%{$query}%")
            ->isActive()
            ->with('category');
            
        if ($businessCategoryId) {
            $subCategoriesQuery->where('business_category_id', $businessCategoryId);
        }
        
        $subCategories = $subCategoriesQuery->limit(3)->get();

        foreach ($subCategories as $subCategory) {
            $suggestions[] = [
                'product_name' => null,
                'category_id' => $subCategory->category_id,
                'category_name' => $subCategory->category?->name,
                'sub_category_id' => $subCategory->id,
                'sub_category_name' => $subCategory->name,
                'sort_type' => null,
                'sub_categories' => [],
                'thumbnail' => $subCategory->thumbnail,
                'business_category_name' => null,
            ];
        }

        // Add popular search terms based on query
        $popularTerms = $this->getPopularSearchTerms($query);
        $suggestions = array_merge($suggestions, $popularTerms);

        // Remove duplicates
        $suggestions = $this->removeDuplicates($suggestions);

        // Limit to 10 suggestions
        $suggestions = array_slice($suggestions, 0, 10);

        return $this->json(
            'Search suggestions retrieved successfully',
            [
                'suggestions' => $suggestions,
                'query' => $query,
            ],
            Response::HTTP_OK
        );
    }

    /**
     * Get popular search terms
     *
     * @param string $query
     * @return array
     */
    private function getPopularSearchTerms($query)
    {
        $terms = [];
        
        // You can enhance this with actual analytics data
        // For now, we'll return some smart suggestions based on common patterns
        
        if (stripos($query, 'new') !== false || stripos($query, 'latest') !== false) {
            $terms[] = [
                'product_name' => null,
                'category_id' => null,
                'category_name' => 'New Arrivals',
                'sub_category_id' => null,
                'sub_category_name' => null,
                'sort_type' => 'latest',
                'sub_categories' => [],
            ];
        }
        
        if (stripos($query, 'popular') !== false || stripos($query, 'best') !== false) {
            $terms[] = [
                'product_name' => null,
                'category_id' => null,
                'category_name' => 'Popular Products',
                'sub_category_id' => null,
                'sub_category_name' => null,
                'sort_type' => 'popular',
                'sub_categories' => [],
            ];
        }
        
        return $terms;
    }

    /**
     * Remove duplicate suggestions
     *
     * @param array $suggestions
     * @return array
     */
    private function removeDuplicates($suggestions)
    {
        $unique = [];
        $seen = [];

        foreach ($suggestions as $suggestion) {
            $key = ($suggestion['product_name'] ?? '') . 
                   ($suggestion['category_id'] ?? '') . 
                   ($suggestion['sub_category_id'] ?? '');
            
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $suggestion;
            }
        }

        return $unique;
    }
}

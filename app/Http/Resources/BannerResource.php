<?php

namespace App\Http\Resources;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $category = null;
        $sub_category = null;
        $sub_categories = collect();
        $type = $this->slider_type;
        $link = $this->slider_link;

        if ($type == 'category' && $link != null) {
            $category = Category::query()->with('subCategories')->find((int) $link);
            $sub_categories = $category?->subCategories ?? collect();
        }

        if ($type == 'sub_category') {
            if ($link != null) {
                $sub_category = SubCategory::find((int)$link);
            }
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'thumbnail' => $this->thumbnail,
            'shop_id' => $this->shop_id,
            'slider_type' => $this->slider_type,
            'slider_link' => $this->slider_link,
            'category_name' => $category?->name,
            'slider_position' => $this->slider_position,
            'business_category_id' => $this->business_category_id,
            // 'category' => CategoryResource::make($category),
            'sub_category' => SubCategoryResource::make($sub_category),
            'sub_categories' => SubCategoryResource::collection($sub_categories),
        ];
    }
}

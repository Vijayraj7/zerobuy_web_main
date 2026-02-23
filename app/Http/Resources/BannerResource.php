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
        $category_id = null;
        $sub_category = null;
        $sub_category_id = null;
        $type = $this->slider_type;
        $link = $this->slider_link;
        if ($type == 'sub_category') {
            if ($link != null) {
                $sub_category = SubCategory::find((int)$link);
                if ($sub_category) {
                    $sub_category_id = $sub_category->id;
                }
            }
        }
        return [
            'id' => $this->id,
            'title' => $this->title,
            'thumbnail' => $this->thumbnail,
            'shop_id' => $this->shop_id,
            'slider_type' => $this->slider_type,
            'slider_link' => $this->slider_link,
            'slider_position' => $this->slider_position,
            'business_category_id' => $this->business_category_id,
            // 'category' => CategoryResource::make($category),
            'sub_category' => SubCategoryResource::make($sub_category),
        ];
    }
}

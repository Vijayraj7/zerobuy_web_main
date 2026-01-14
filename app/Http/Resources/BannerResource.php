<?php

namespace App\Http\Resources;

use App\Models\Category;
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
        $type = $this->slider_type;
        $link = $this->slider_link;
        if ($type == 'sub_category') {
            if ($link != null) {
                $category = Category::find((int)$link);
                if ($category) {
                    $category_id = $category->id;
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
            'category' => CategoryResource::make($category),
        ];
    }
}

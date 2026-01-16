<?php

namespace App\Http\Resources\Seller;

use App\Http\Resources\ProductResource;
use App\Http\Resources\SellerProductResource;
use App\Http\Resources\ShopResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvertisementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_id' => $this->shop_id,
            'ads_type' => $this->ads_type,
            'product_id' => $this->product_id,
            'shop' => $this->shop ? ShopResource::make($this->shop) : null,
            'product' => $this->product ? SellerProductResource::make($this->product) : null,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'daily_budget' => $this->daily_budget,
            'total_budget' => $this->total_budget,
            'total_views' => $this->total_views,
            // 'status' => $this->status,
            'status' => $this->getStatusName(),
        ];
    }
}

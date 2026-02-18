<?php

namespace App\Http\Resources\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlashSaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'thumbnail' => $this->thumbnail,
            'start_date' => $this->start_date && $this->start_time
                ? $this->start_date.' '.$this->start_time
                : $this->start_date,
            'end_date' => $this->end_date && $this->end_time
                ? $this->end_date.' '.$this->end_time
                : $this->end_date,
            'min_discount' => (float) ($this->min_discount ?? $this->discount ?? 0),
            'discount' => (float) ($this->discount ?? 0),
            'status' => (bool) $this->status,
        ];
    }
}

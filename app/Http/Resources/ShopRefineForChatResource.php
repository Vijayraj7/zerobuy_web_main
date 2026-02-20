<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopRefineForChatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lastOnline = (bool) $this->isOnline();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo,
            'rating' => (float) number_format($this->averageRating, 1, '.', ''),
            'last_online' => $lastOnline
        ];
    }
}

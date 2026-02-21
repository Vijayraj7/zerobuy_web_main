<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title ?? '',
            'content' => $this->content ?? '',
            'message' => $this->content ?? '',
            'url' => $this->url,
            'icon' => $this->icon,
            'type' => $this->type ?? '',
            'shop_id' => (int)$this->shop_id,
            'user_id' => (int)$this->user_id,
            'is_read' => (bool) $this->is_read,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
            'withdraw_id' => (int)$this->withdraw_id,

        ];
    }
}

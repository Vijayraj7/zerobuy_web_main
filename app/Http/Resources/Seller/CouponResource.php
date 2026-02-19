<?php

namespace App\Http\Resources\Seller;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $startedAt = $this->started_at ? Carbon::parse($this->started_at) : null;
        $expiredAt = $this->expired_at ? Carbon::parse($this->expired_at) : null;

        return [
            'id' => $this->id,
            'code' => (string) $this->code,
            'discount_type' => $this->type?->value ?? (string) $this->type,
            'discount' => (float) $this->discount,
            'min_order_amount' => (float) $this->min_amount,
            'max_discount_amount' => $this->max_discount_amount !== null ? (float) $this->max_discount_amount : null,
            'limit_for_user' => $this->limit_for_user !== null ? (int) $this->limit_for_user : null,
            'started_at' => $startedAt?->toIso8601String(),
            'expired_at' => $expiredAt?->toIso8601String(),
            'start_date' => $startedAt?->format('Y-m-d'),
            'start_time' => $startedAt?->format('H:i'),
            'expired_date' => $expiredAt?->format('Y-m-d'),
            'expired_time' => $expiredAt?->format('H:i'),
            'is_active' => (bool) $this->is_active,
        ];
    }
}

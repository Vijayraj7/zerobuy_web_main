<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdTransactionResource extends JsonResource
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
            'ad_transaction_id' => $this->ad_transaction_id,
            'wallet_id' => (int)$this->ad_wallet_id,
            'amount' => (float)$this->amount,
            'is_commission' => (int)$this->is_commission,
            'type' => $this->type,
            'transaction_id' => $this->transaction_id,
            'purpose' => $this->purpose,
            'note' => $this->note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

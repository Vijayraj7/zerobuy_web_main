<?php

namespace App\Http\Resources;

use App\Models\District;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $state =  State::find($this->state_id);
        $district =  District::find($this->district_id);
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'state' => $state->name,
            'district' => $district->name,
            'area' => $this->area,
            'state_id' => $state?->id ?? $this->state_id,
            'district_id' => $district?->id ?? $this->district_id,
            'flat_no' => $this->flat_no,
            'address_type' => $this->address_type,
            'address_line' => $this->address_line,
            'address_line2' => $this->address_line2,
            'post_code' => $this->post_code,
            'is_default' => (bool) $this->is_default,
            'latitude' => $this->latitude,
            'latitude' => $this->latitude,
        ];
    }
}

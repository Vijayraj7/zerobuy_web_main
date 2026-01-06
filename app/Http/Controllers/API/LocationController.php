<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SellerOrderResource;
use App\Http\Resources\StateResource;
use Illuminate\Http\Request;
use App\Models\District;
use App\Models\State;

class LocationController extends Controller
{
    public function getDistricts($stateId)
    {
        return response()->json([
            'data' => District::where('state_id', $stateId)
                ->orderBy('name')
                ->get()
        ]);
    }

    public function getStates()
    {
        return $this->json('okay', [
            'states' => StateResource::collection(State::with('districts')->get())->resolve()
        ]);
    }
}

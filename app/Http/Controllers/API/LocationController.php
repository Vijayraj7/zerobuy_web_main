<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\District;

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
}

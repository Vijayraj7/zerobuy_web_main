<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ShopReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ShopReportController extends Controller
{
    /**
     * Store a new shop report
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_id' => 'required|exists:shops,id',
            'reason' => 'required|string|max:255',
            'comment' => 'nullable|string|max:1000',
            'images' => 'nullable|array|max:4',
            'images.*' => 'nullable|image|mimes:jpeg,jpg,png|max:5120', // 5MB max per image
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::guard('api')->user();
            
            if (!$user || !$user->customer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer not found',
                ], 401);
            }

            // Handle image uploads
            $imagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    if ($image && $image->isValid()) {
                        $path = $image->store('shop_reports', 'public');
                        $imagePaths[] = $path;
                    }
                }
            }

            $report = ShopReport::create([
                'customer_id' => $user->customer->id,
                'shop_id' => $request->shop_id,
                'reason' => $request->reason,
                'comment' => $request->comment,
                'images' => !empty($imagePaths) ? $imagePaths : null,
                'status' => 'pending',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Report submitted successfully. We will review your report shortly.',
                'data' => [
                    'report_id' => $report->id,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get customer's reports
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();
            
            if (!$user || !$user->customer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer not found',
                ], 401);
            }

            $reports = ShopReport::where('customer_id', $user->customer->id)
                ->with(['shop:id,name,logo'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'status' => 'success',
                'message' => 'Reports retrieved successfully',
                'data' => $reports,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve reports',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductStatus;
use App\Models\Product;

class ProductStatusController extends Controller
{
    public function index()
    {
        $shop = generaleSetting('shop');
 
        $statuses = ProductStatus::with('product.media')
            ->where('shop_id', $shop->id)
            ->where(function ($q) {
                $q->whereNull('expired_at')
                ->orWhere('expired_at', '>', now());
            })
            ->latest()
            ->get();

        $products = Product::with('media')
            ->where('shop_id', $shop->id)
            ->latest()
            ->get();

        return view('admin.product.make-status', compact('statuses', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'message'    => 'nullable|string',
        ]);

        $shop = generaleSetting('shop');
 
        $exists = ProductStatus::where('product_id', $request->product_id)
            ->where('expired_at', '>', now())
            ->exists();

        if ($exists) {
            return back()->with('error', 'Status already active. Wait until it expires.');
        }

        ProductStatus::create([
            'shop_id'    => $shop->id,
            'product_id' => $request->product_id,
            'message'    => $request->message,
            'is_active'  => 1,
            'started_at' => now(),
            'expired_at' => now()->addHours(24),
        ]);

        return back()->with('success', 'Status activated for 24 hours');
    }

    public function toggle(ProductStatus $status)
    { 
        if ($status->expired_at && $status->expired_at->lte(now())) {
            return back()->with('error', 'Status expired. Create a new status.');
        }
 
        if (!$status->is_active) {
            $status->update([
                'is_active'  => 1,
                'started_at' => now(),
                'expired_at' => now()->addHours(24),
            ]);

            return back()->with('success', 'Status activated for next 24 hours');
        }
 
        $status->update([
            'is_active' => 0,
        ]);

        return back()->with('success', 'Status deactivated');
    }


    // public function toggle(ProductStatus $status)
    // { 
    //     if ($status->expired_at && $status->expired_at->lte(now())) {
    //         return back()->with('error', 'Status expired. Create a new one.');
    //     }

    //     $status->update([
    //         'is_active' => !$status->is_active,
    //     ]);

    //     return back()->with('success', 'Status updated');
    // }

    public function destroy(ProductStatus $status)
    {
        $status->delete();
        return back()->with('success', 'Status deleted');
    }
}

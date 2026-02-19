<?php

namespace App\Http\Controllers\API\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\CouponRequest;
use App\Http\Resources\Seller\CouponResource;
use App\Models\Coupon;
use App\Repositories\CouponRepository;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * Display a listing of the coupons for the current shop.
     */
    public function index(Request $request)
    {
        $shop = generaleSetting('shop');

        $coupons = CouponRepository::query()
            ->where('shop_id', $shop->id)
            ->latest('id')
            ->get();

        return $this->json('Shop coupons', [
            'coupons' => CouponResource::collection($coupons),
        ]);
    }

    /**
     * Store a newly created coupon.
     */
    public function store(CouponRequest $request)
    {
        $shop = generaleSetting('shop');

        $coupon = CouponRepository::storeByRequest($request, $shop->id);

        return $this->json('Coupon created successfully', [
            'coupon' => CouponResource::make($coupon),
        ]);
    }

    /**
     * Update the specified coupon.
     */
    public function update(CouponRequest $request, Coupon $coupon)
    {
        $shop = generaleSetting('shop');

        if ((int) $coupon->shop_id !== (int) $shop->id) {
            return $this->json('Coupon not found', [], 404);
        }

        $coupon = CouponRepository::updateByRequest($request, $coupon);

        return $this->json('Coupon updated successfully', [
            'coupon' => CouponResource::make($coupon),
        ]);
    }

    /**
     * Remove the specified coupon.
     */
    public function destroy(Coupon $coupon)
    {
        $shop = generaleSetting('shop');

        if ((int) $coupon->shop_id !== (int) $shop->id) {
            return $this->json('Coupon not found', [], 404);
        }

        $coupon->delete();

        return $this->json('Coupon deleted successfully');
    }

    /**
     * Toggle coupon active status.
     */
    public function toggleStatus(Coupon $coupon)
    {
        $shop = generaleSetting('shop');

        if ((int) $coupon->shop_id !== (int) $shop->id) {
            return $this->json('Coupon not found', [], 404);
        }

        $coupon->update([
            'is_active' => ! $coupon->is_active,
        ]);

        return $this->json('Coupon status updated', [
            'coupon' => CouponResource::make($coupon),
        ]);
    }
}

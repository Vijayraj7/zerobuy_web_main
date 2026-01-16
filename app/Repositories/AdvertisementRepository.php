<?php

namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Models\Advertisement;
use App\Models\AdvertisementSetting;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class AdvertisementRepository extends Repository
{
    /**
     * base method
     *
     * @method model()
     */
    public static function model()
    {
        return Advertisement::class;
    }

    public static function storeByRequest(Request $request)
    {
        $request->validate([
            'ads_type' => 'required|in:store,product',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'product_id' => 'required_if:ads_type,product'
        ]);

        $shop = generaleSetting('shop');
        $wallet = Wallet::where('user_id', $shop->user_id)->first();
        $today = Carbon::today();

        // SINGLE ACTIVE RULE
        if ($request->ads_type === 'store') {
            $exists = Advertisement::where('shop_id', $shop->id)
                ->where('ads_type', 'store')
                ->where('status', 'active')
                ->whereDate('end_date', '>=', $today)
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'Shop already has active advertisement'], 400);
            }
        }

        if ($request->ads_type === 'product') {
            $exists = Advertisement::where('shop_id', $shop->id)
                ->where('ads_type', 'product')
                ->where('product_id', $request->product_id)
                ->where('status', 'active')
                ->whereDate('end_date', '>=', $today)
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'Product already has active advertisement'], 400);
            }
        }

        $dailyBudget = AdvertisementSetting::first()->daily_budget;
        $days = Carbon::parse($request->start_date)
            ->diffInDays(Carbon::parse($request->end_date)) + 1;

        $total = $days * $dailyBudget;

        if ($wallet->balance < $total) {
            return response()->json(['message' => 'Insufficient wallet balance'], 400);
        }

        DB::transaction(function () use ($request, $shop, $wallet, $dailyBudget, $total) {

            Advertisement::create([
                'shop_id' => $shop->id,
                'ads_type' => $request->ads_type,
                'product_id' => $request->ads_type == 'product'
                    ? $request->product_id
                    : null,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'daily_budget' => $dailyBudget,
                'total_budget' => $total,
                'status' => 'active'
            ]);

            $wallet->decrement('balance', $total);

            Transaction::create([
                'wallet_id' => $wallet->id,
                'amount' => $total,
                'type' => 'debit',
                'purpose' => 'Ads Run',
                'note' => 'Advertisement'
            ]);
        });
    }
}

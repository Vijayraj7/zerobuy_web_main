<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RevenueController extends Controller
{
    public function index(Request $request)
    {
         
        // CHART MODE (today | month | year | custom) 
        $mode = $request->get('mode', 'year');

        if ($mode === 'today') {
            $startDate = Carbon::today();
            $endDate   = Carbon::today()->endOfDay();
        }
        elseif ($mode === 'month') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate   = Carbon::now()->endOfMonth();
        }
        elseif ($mode === 'custom' && $request->start_date && $request->end_date) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate   = Carbon::parse($request->end_date)->endOfDay();
        }
        else { // year (default)
            $startDate = Carbon::now()->startOfYear();
            $endDate   = Carbon::now()->endOfYear();
        }

        //SUMMARY CARDS (UNCHANGED) 
        $totalSubscriptionSales = DB::table('shop_subscriptions')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalSubscriptionAmount = DB::table('shop_subscriptions')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('price');

        $totalAds = DB::table('advertisements')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalAdsAmount = DB::table('advertisements')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_budget');

        $walletBalance = DB::table('ad_wallets')->sum('balance');

        //CHART DATA 
        $subscriptionChart = [];
        $adsChart = [];
        $labels = [];

        if ($mode === 'today') {

            $labels = range(0, 23);

            $subs = DB::table('shop_subscriptions')
                ->selectRaw('HOUR(created_at) as label, SUM(price) as total')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('label')
                ->pluck('total', 'label')
                ->toArray();

            $ads = DB::table('advertisements')
                ->selectRaw('HOUR(created_at) as label, SUM(total_budget) as total')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('label')
                ->pluck('total', 'label')
                ->toArray();

            foreach ($labels as $h) {
                $subscriptionChart[] = $subs[$h] ?? 0;
                $adsChart[] = $ads[$h] ?? 0;
            }
        }

        elseif ($mode === 'month') {

            $days = $startDate->daysInMonth;
            $labels = range(1, $days);

            $subs = DB::table('shop_subscriptions')
                ->selectRaw('DAY(created_at) as label, SUM(price) as total')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('label')
                ->pluck('total', 'label')
                ->toArray();

            $ads = DB::table('advertisements')
                ->selectRaw('DAY(created_at) as label, SUM(total_budget) as total')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('label')
                ->pluck('total', 'label')
                ->toArray();

            foreach ($labels as $d) {
                $subscriptionChart[] = $subs[$d] ?? 0;
                $adsChart[] = $ads[$d] ?? 0;
            }
        }

        else { // year & custom

            $labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

            $subs = DB::table('shop_subscriptions')
                ->selectRaw('MONTH(created_at) as label, SUM(price) as total')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('label')
                ->pluck('total', 'label')
                ->toArray();

            $ads = DB::table('advertisements')
                ->selectRaw('MONTH(created_at) as label, SUM(total_budget) as total')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('label')
                ->pluck('total', 'label')
                ->toArray();

            for ($i = 1; $i <= 12; $i++) {
                $subscriptionChart[] = $subs[$i] ?? 0;
                $adsChart[] = $ads[$i] ?? 0;
            }
        }

        return view('admin.revenue.index', compact(
            'totalSubscriptionSales',
            'totalSubscriptionAmount',
            'totalAds',
            'totalAdsAmount',
            'walletBalance',
            'subscriptionChart',
            'adsChart',
            'labels',
            'mode'
        ));
    }
}
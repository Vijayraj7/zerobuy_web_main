<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdTransaction;
use App\Models\AdWallet;
use Illuminate\Http\Request;
use App\Models\Advertisement;
use App\Models\Product;
// use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\AdvertisementSetting;
use App\Models\Shop;
use Carbon\Carbon;
use DataTables;
use DB;

class AdvertismentController extends Controller
{
    public function index(Request $request)
    {
        // AUTO EXPIRE ADS
        Advertisement::where('status', 'active')
            ->whereDate('end_date', '<', Carbon::today())
            ->update(['status' => 'completed']);

        $shop    = generaleSetting('shop');
        $wallet  = AdWallet::where('user_id', $shop->user_id)->first();
        $setting = AdvertisementSetting::first();

        // AUTO EXPIRE ADS
        Advertisement::where('status', 'active')
            ->whereDate('end_date', '<', Carbon::today())
            ->update(['status' => 'completed']);

        if ($request->ajax()) {
            $ads = Advertisement::with('product')
                ->where('shop_id', $shop->id);

            return DataTables::of($ads)
                ->addIndexColumn()
                ->editColumn('start_date', fn($r) => $r->start_date->format('d-m-Y'))
                ->editColumn('end_date', fn($r) => $r->end_date->format('d-m-Y'))
                ->addColumn(
                    'product_image',
                    fn($r) =>
                    $r->product
                        ? '<img src="' . asset($r->product->thumbnail) . '" width="40">'
                        : 'N/A'
                )
                ->addColumn(
                    'product_id',
                    fn($r) =>
                    $r->product_id ? 'PRD0' . $r->product_id : 'N/A'
                )
                ->addColumn('product_name', fn($r) => $r->product?->name ?? 'N/A')
                ->editColumn('daily_budget', fn($r) => '₹' . $r->daily_budget)
                ->editColumn('total_budget', fn($r) => '₹' . $r->total_budget)
                // ->addColumn('status',fn($r)=>
                //     $r->status=='active'
                //         ? '<span class="badge bg-success">Active</span>'
                //         : '<span class="badge bg-secondary">Completed</span>'
                // )
                ->addColumn('status', function ($r) {
                    $today = Carbon::today();
                    $start = Carbon::parse($r->start_date);
                    $end   = Carbon::parse($r->end_date);
                    // UPCOMING
                    if ($start->gt($today)) {
                        $days = $today->diffInDays($start);

                        if ($days == 1) {
                            return '<span class="badge bg-info">Starts Tomorrow</span>';
                        }

                        return '<span class="badge bg-info">Starts in ' . $days . ' days</span>';
                    }
                    // ACTIVE
                    if ($today->between($start, $end)) {
                        return '<span class="badge bg-success">Active</span>';
                    }
                    // COMPLETED
                    return '<span class="badge bg-secondary">Completed</span>';
                })

                ->rawColumns(['product_image', 'status'])
                ->make(true);
        }

        return view('admin.advertisement.index', compact('wallet', 'setting'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ads_type'   => 'required|in:store,product',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'product_id' => 'required_if:ads_type,product'
        ]);

        $shop   = generaleSetting('shop');
        $wallet = AdWallet::where('user_id', $shop->user_id)->first();
        $today  = Carbon::today();

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
                'shop_id'      => $shop->id,
                'ads_type'     => $request->ads_type,
                'product_id'   => $request->ads_type == 'product'
                    ? $request->product_id
                    : null,
                'start_date'   => $request->start_date,
                'end_date'     => $request->end_date,
                'daily_budget' => $dailyBudget,
                'total_budget' => $total,
                'status'       => 'active'
            ]);

            $wallet->decrement('balance', $total);

            AdTransaction::create([
                'wallet_id' => $wallet->id,
                'amount' => $total,
                'type' => 'debit',
                'purpose' => 'Ads Run',
                'note' => 'Advertisement'
            ]);
        });

        return response()->json(['message' => 'Advertisement created successfully']);
    }

    public function products(Request $request)
    {
        $shop = generaleSetting('shop');

        return Product::where('shop_id', $shop->id)
            ->where('is_active', 1)
            ->where(
                fn($q) =>
                $q->where('name', 'like', "%{$request->q}%")
                    ->orWhere('id', 'like', "%{$request->q}%")
            )
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'image' => asset($p->thumbnail),
            ]);
    }

    public function transactions()
    {
        $shop = generaleSetting('shop');

        $wallet = AdWallet::where('user_id', $shop->user_id)->first();

        // CASE 1: Wallet not created
        if (!$wallet) {
            return view('admin.advertisement.transaction-list', [
                'transactions' => collect(),
                'message' => 'Wallet not found for this user.'
            ]);
        }

        $transactions = Transaction::where('wallet_id', $wallet->id)
            ->where('purpose', 'Ads Run')
            ->latest()
            ->get();

        return view('admin.advertisement.transaction-list', [
            'transactions' => $transactions,
            'message' => null
        ]);
    }
    
    public function allAds(Request $request)
    {

        if ($request->ajax()) {
            $today = Carbon::today();
            $ads = Advertisement::query()
                ->with(['shop.products','shop.orders','shop.subscriptions','shop.adwallet',])
                ->where('status', 'active')
                ->where('ads_type', 'store')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today);

            if ($request->filled('start_date')) {
                $ads->whereDate('advertisements.created_at', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $ads->whereDate('advertisements.created_at', '<=', $request->end_date);
            }

            return DataTables::eloquent($ads)
            ->addIndexColumn() 

            ->addColumn('create_date', fn ($r) =>
                $r->created_at->format('d-m-Y | h:i A')
            )  
            ->addColumn('store_id', fn($r) => 
                'STR0' . $r->shop_id 
            )
            ->addColumn('store_name', fn ($r) =>
                $r->shop->name
            ) 
            ->addColumn('state', fn ($r) =>
                $r->shop->state
            ) 
            ->addColumn('total_products', fn ($r) =>
                $r->shop->products->count()
            ) 
            ->addColumn('total_orders', fn ($r) =>
                $r->shop->orders->count()
            ) 
            ->addColumn('subscription', function ($r) {
                $subscription = $r->shop->currentSubscription()->with('plan')->first();
                $daysLeft = 0;
                $totalDays = 0;

                if ($subscription && $subscription->ends_at && $subscription->starts_at) { 
                    $daysLeft = max(0, now()->startOfDay()->diffInDays($subscription->ends_at->startOfDay()));
                    $totalDays = $subscription->starts_at->diffInDays($subscription->ends_at);
                }
                return $totalDays . ' Days';
            }) 
            ->addColumn('wallet_amount', fn ($r) =>
                '₹ ' . ($r->shop->wallet->balance ?? 0)
            ) 
            ->addColumn('status', fn () =>
                '<span class="badge bg-success">Active</span>'
            ) 
            ->addColumn('actions', fn ($r) =>
                '<a href="' . route('admin.advertisement.shop.ads', $r->shop_id) . '" class="btn btn-sm btn-outline-primary">
                    <i class="fa fa-eye"></i>
                </a>'
            )

            /* ---------- SEARCHING ---------- */ 
            ->filterColumn('store_id', function ($q, $k) {
                $k = str_replace('STR0', '', $k);
                $q->where('advertisements.shop_id', 'like', "%{$k}%");
            })

            ->filterColumn('store_name', fn ($q, $k) =>
                $q->whereHas('shop', fn ($s) =>
                    $s->where('name', 'like', "%{$k}%")
                )
            )
            ->filterColumn('state', fn ($q, $k) =>
                $q->whereHas('shop', fn ($s) =>
                    $s->where('state', 'like', "%{$k}%")
                )
            )
            /* ---------- SORTING ---------- */ 
            ->orderColumn( 'store_id', fn($q, $k) => 
                $q->orderBy('advertisements.shop_id', $k) 
            )
            ->orderColumn('store_name', fn ($q, $o) =>
                $q->join('shops', 'shops.id', '=', 'advertisements.shop_id')
                ->orderBy('shops.name', $o)
            )
            ->rawColumns(['status', 'actions'])
            ->make(true);
        }

        return view('admin.advertisement.all-ads');
    }

    public function shopAds($shop_id, Request $request)
    {
        Advertisement::where('status', 'active')
            ->whereDate('end_date', '<', Carbon::today())
            ->update(['status' => 'completed']);

   
        $shop = Shop::findOrFail($shop_id);

        $wallet  = AdWallet::where('user_id', $shop->user_id)->first();
        $setting = AdvertisementSetting::first();

        if ($request->ajax()) {

            $ads = Advertisement::with('product')->where('shop_id', $shop_id);

            if ($request->filled('start_date')) {
                $ads->whereDate('start_date', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $ads->whereDate('end_date', '<=', $request->end_date);
            }

            return DataTables::of($ads)
                ->addIndexColumn()

                ->editColumn('start_date', fn ($r) =>
                    Carbon::parse($r->start_date)->format('d-m-Y')
                )

                ->editColumn('end_date', fn ($r) =>
                    Carbon::parse($r->end_date)->format('d-m-Y')
                )
                
                ->addColumn('ads_id', fn ($r) =>
                    'ADS0' . $r->id
                )
                ->filterColumn('ads_id', function ($q, $k) {
                    $k = str_replace('ADS0', '', $k);
                    $q->where('id', 'like', "%{$k}%");
                })
                ->orderColumn('ads_id', fn ($q, $o) =>
                    $q->orderBy('id', $o)
                )
                ->addColumn('product_image', fn ($r) =>
                    $r->product
                        ? '<img src="' . asset($r->product->thumbnail) . '" width="40">'
                        : 'N/A'
                )
                ->addColumn('product_id', fn ($r) =>
                    $r->product_id ? 'PRD0' . $r->product_id : 'N/A'
                )
                ->filterColumn('product_id', function ($q, $k) {
                    $k = str_replace('PRD0', '', $k);
                    $q->where('product_id', 'like', "%{$k}%");
                })
                ->addColumn('product_name', fn ($r) =>
                    $r->product?->name ?? 'N/A'
                )
                ->filterColumn('product_name', function ($q, $k) {
                    $q->whereHas('product', fn ($p) =>
                        $p->where('name', 'like', "%{$k}%")
                    );
                })
                ->orderColumn('product_name', fn ($q, $o) =>
                    $q->join('products', 'products.id', '=', 'advertisements.product_id')
                    ->orderBy('products.name', $o)
                )

                ->editColumn('daily_budget', fn ($r) =>
                    '₹' . $r->daily_budget
                )

                ->editColumn('total_budget', fn ($r) =>
                    '₹' . $r->total_budget
                )

                ->addColumn('status', function ($r) {
                    $today = Carbon::today();
                    $start = Carbon::parse($r->start_date);
                    $end   = Carbon::parse($r->end_date);

                    if ($start->gt($today)) {
                        $days = $today->diffInDays($start);
                        return $days == 1
                            ? '<span class="badge bg-info">Starts Tomorrow</span>'
                            : '<span class="badge bg-info">Starts in ' . $days . ' days</span>';
                    }

                    if ($today->between($start, $end)) {
                        return '<span class="badge bg-success">Active</span>';
                    }

                    return '<span class="badge bg-secondary">Completed</span>';
                })
                ->addColumn('actions', function ($r) { 
                    if ($r->status === 'completed') {
                        return '
                            <button class="btn btn-sm btn-secondary" disabled title="Completed ads cannot be deleted">
                                <i class="fa fa-lock"></i>
                            </button>
                        ';
                    }

                    return '
                        <button
                            class="btn btn-sm btn-danger delete-ad"
                            data-id="' . $r->id . '">
                            <i class="fa fa-trash"></i>
                        </button>
                    ';
                })


                ->rawColumns(['product_image', 'status', 'actions'])
                ->make(true);
        }

        return view('admin.advertisement.shop-ads-view', compact('wallet', 'setting', 'shop'));
    }

    public function destroy($id)
    {
        $ad = Advertisement::findOrFail($id);
 
        if ($ad->status === 'completed') {
            return response()->json([
                'status'  => false,
                'message' => 'Completed ads cannot be deleted.'
            ], 403);
        }

        $ad->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Advertisement deleted successfully.'
        ]);
    }

}

<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\AdTransaction;
use App\Models\AdWallet;
use Illuminate\Http\Request;
use App\Models\Advertisement;
use App\Models\Product;
use App\Models\PaymentGateway;
// use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\AdvertisementSetting;
use Carbon\Carbon;
use DataTables;
use DB;
use Razorpay\Api\Api;

class AdvertismentController extends Controller
{
    public function index(Request $request)
    {
        // AUTO EXPIRE ADS
        Advertisement::where('status', 'active')
            ->whereDate('end_date', '<', Carbon::today())
            ->update(['status' => 'completed']);

        $shop = generaleSetting('shop');
        $wallet = AdWallet::where('user_id', $shop->user_id)->first();
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
                    $end = Carbon::parse($r->end_date);
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

        return view('shop.advertisement.index', compact('wallet', 'setting'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ads_type' => 'required|in:store,product',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'product_id' => 'required_if:ads_type,product'
        ]);

        $shop = generaleSetting('shop');
        $wallet = AdWallet::where('user_id', $shop->user_id)->first();
        $today = Carbon::today();

        // SINGLE ACTIVE RULE
        if ($request->ads_type === 'store') {
            $exists = Advertisement::where('shop_id', $shop->id)
                ->where('ads_type', 'store')
                ->where('status', 'active')
                ->whereDate('end_date', '>=', $today)
                ->exists();

            if ($exists) {
                return $this->json('Shop already has active advertisement', 400);
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
                return $this->json('Product already has active advertisement', 400);
            }
        }

        $dailyBudget = AdvertisementSetting::first()->daily_budget;
        $days = Carbon::parse($request->start_date)
            ->diffInDays(Carbon::parse($request->end_date)) + 1;

        $total = $days * $dailyBudget;

        if ($wallet->balance < $total) {
            return $this->json('Insufficient wallet balance', [], 400);
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

            AdTransaction::create([
                'ad_wallet_id' => $wallet->id,
                'amount' => $total,
                'type' => 'debit',
                'purpose' => 'Ads Run',
                'note' => 'Advertisement'
            ]);
        });

        return $this->json('Advertisement created successfully', [], 200);
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
            return view('shop.advertisement.transaction-list', [
                'transactions' => collect(),
                'message' => 'Wallet not found for this user.'
            ]);
        }

        $transactions = Transaction::where('wallet_id', $wallet->id)
            ->where('purpose', 'Ads Run')
            ->latest()
            ->get();

        return view('shop.advertisement.transaction-list', [
            'transactions' => $transactions,
            'message' => null
        ]);
    }

    public function createWalletOrder(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $shop = generaleSetting('shop');

        // Get Razorpay credentials from PaymentGateway table
        $razorpay = PaymentGateway::where('name', 'Razorpay')
            ->where('is_active', 1)
            ->first();

        if (!$razorpay) {
            return response()->json([
                'status' => false,
                'message' => 'Razorpay payment gateway is not configured or inactive'
            ], 400);
        }

        $credentials = json_decode($razorpay->config, true);
        $key = $credentials['key'] ?? null;
        $secret = $credentials['secret'] ?? null;

        if (!$key || !$secret) {
            return response()->json([
                'status' => false,
                'message' => 'Razorpay credentials are missing'
            ], 400);
        }

        try {
            $api = new Api($key, $secret);

            $amount = $request->amount * 100; // Convert to paise

            $order = $api->order->create([
                'amount' => $amount,
                'currency' => 'INR',
                'receipt' => 'adwallet_' . $shop->id . '_' . time(),
                'notes' => [
                    'shop_id' => $shop->id,
                    'user_id' => $shop->user_id,
                    'purpose' => 'Ad Wallet Recharge'
                ]
            ]);

            \Log::info('Razorpay Order Created', [
                'order_id' => $order->id,
                'amount' => $order->amount,
                'currency' => $order->currency
            ]);

            return response()->json([
                'status' => true,
                'order' => [
                    'id' => $order->id,
                    'amount' => $order->amount,
                    'currency' => $order->currency,
                    'receipt' => $order->receipt
                ],
                'razorpay_key' => $key
            ]);
        } catch (\Exception $e) {
            \Log::error('Razorpay Order Creation Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verifyWalletPayment(Request $request)
    {
        $request->validate([
            'razorpay_payment_id' => 'required',
            'razorpay_order_id' => 'required',
            'razorpay_signature' => 'required',
            'amount' => 'required|numeric|min:1'
        ]);

        $shop = generaleSetting('shop');

        // Get Razorpay credentials
        $razorpay = PaymentGateway::where('name', 'Razorpay')
            ->where('is_active', 1)
            ->first();

        if (!$razorpay) {
            return response()->json([
                'status' => false,
                'message' => 'Razorpay payment gateway not found'
            ], 400);
        }

        $credentials = json_decode($razorpay->config, true);
        $secret = $credentials['secret'] ?? null;

        if (!$secret) {
            return response()->json([
                'status' => false,
                'message' => 'Razorpay secret key is missing'
            ], 400);
        }

        try {
            // Verify signature
            $api = new Api($credentials['key'], $secret);

            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature
            ];

            $api->utility->verifyPaymentSignature($attributes);

            DB::beginTransaction();

            // Get or create AdWallet
            $wallet = AdWallet::where('user_id', $shop->user_id)->first();

            // Add amount to wallet
            $wallet->balance += $request->amount;
            $wallet->save();

            // Create transaction record
            AdTransaction::create([
                'ad_wallet_id' => $wallet->id,
                'amount' => $request->amount,
                'is_commission' => 0,
                'type' => 'credit',
                'transaction_id' => $request->razorpay_payment_id,
                'purpose' => 'Wallet Recharge',
                'note' => 'Razorpay Payment - Order: ' . $request->razorpay_order_id
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => '₹' . number_format($request->amount, 2) . ' added to your ad wallet successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Payment verification failed: ' . $e->getMessage()
            ], 400);
        }
    }
}

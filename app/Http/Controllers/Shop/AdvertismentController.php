<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\AdTransaction;
use App\Models\AdWallet;
use App\Models\AdPaymentOrder;
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

            $ad_new = Advertisement::create([
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
                'transaction_id' => $ad_new->id,
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

        $transactions = AdTransaction::where('ad_wallet_id', $wallet->id)
            // ->where('purpose', 'Ads Run')
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

            // Get or create AdWallet
            $wallet = AdWallet::firstOrCreate(
                ['user_id' => $shop->user_id],
                ['balance' => 0]
            );

            // Save payment order to database
            AdPaymentOrder::create([
                'user_id' => $shop->user_id,
                'ad_wallet_id' => $wallet->id,
                'razorpay_order_id' => $order->id,
                'amount' => $request->amount,
                'currency' => $order->currency,
                'receipt' => $order->receipt,
                'status' => 'created',
                'notes' => json_encode([
                    'shop_id' => $shop->id,
                    'purpose' => 'Ad Wallet Recharge'
                ])
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

            // Find payment order
            $paymentOrder = AdPaymentOrder::where('razorpay_order_id', $request->razorpay_order_id)->first();

            if (!$paymentOrder) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Payment order not found'
                ], 404);
            }

            // Check if already processed
            if ($paymentOrder->status === 'paid') {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Payment already processed'
                ], 400);
            }

            // Get wallet
            $wallet = AdWallet::where('user_id', $shop->user_id)->first();

            // Add amount to wallet
            $wallet->balance += $request->amount;
            $wallet->save();

            // Update payment order
            $paymentOrder->update([
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
                'status' => 'paid',
                'paid_at' => now()
            ]);

            // Create transaction record
            AdTransaction::create([
                'ad_wallet_id' => $wallet->id,
                'amount' => $request->amount,
                'is_commission' => 0,
                'type' => 'credit',
                'transaction_id' => $request->razorpay_payment_id,
                'purpose' => 'recharge',
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

    public function webhookHandler(Request $request)
    {
        \Log::debug('Webhook');
        \Log::debug('response array ' . json_encode($request->all()));

        $post = file_get_contents('php://input');
        $data = json_decode($post, true);

        if (isset($data['event']) && $data['event'] === 'payment.captured') {
            try {
                $status = $data['payload']['payment']['entity']['status'];
                $raz_order_id = $data['payload']['payment']['entity']['order_id'];
                $raz_payment_id = $data['payload']['payment']['entity']['id'];

                // Find payment order that is not already processed
                $paymentOrder = AdPaymentOrder::where('razorpay_order_id', $raz_order_id)
                    ->where('status', 'created')
                    ->first();

                if ($status === 'captured') {
                    if ($paymentOrder !== null) {
                        $amount = $paymentOrder->amount;

                        // Update wallet balance
                        $wallet = AdWallet::find($paymentOrder->ad_wallet_id);
                        $wallet->balance += $amount;
                        $wallet->save();

                        // Create transaction record
                        $transaction = new AdTransaction();
                        $transaction->ad_wallet_id = $paymentOrder->ad_wallet_id;
                        $transaction->amount = $amount;
                        $transaction->is_commission = 0;
                        $transaction->type = 'credit';
                        $transaction->transaction_id = $raz_payment_id;
                        $transaction->purpose = 'recharge';
                        $transaction->note = 'Wallet Recharge - Order: ' . $raz_order_id;
                        $transaction->save();

                        // Update payment order status
                        $paymentOrder->razorpay_payment_id = $raz_payment_id;
                        $paymentOrder->status = 'paid';
                        $paymentOrder->paid_at = now();
                        $paymentOrder->save();

                        \Log::debug('success: ' . $raz_order_id);
                        echo 200;
                    } else {
                        \Log::debug('Payment order not found or already processed: ' . $raz_order_id);
                        echo 200; // Return success to avoid retries
                    }
                } else {
                    \Log::debug('Invalid payment status: ' . $status . ' for order ID: ' . $raz_order_id);
                    echo 400;
                }
            } catch (\Exception $e) {
                \Log::error('Exception: ' . $e->getMessage());
                echo 500;
            }
        } else {
            \Log::debug('Invalid or missing event in the request: ' . json_encode($data));
            echo 400;
        }
    }

    public function testWebhook()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Webhook endpoint is accessible',
            'url' => route('shop.advertisement.webhook.razorpay'),
            'timestamp' => now()
        ]);
    }

    public function processPendingPayment($orderId)
    {
        try {
            // Find payment order
            $paymentOrder = AdPaymentOrder::where('razorpay_order_id', $orderId)->first();

            if (!$paymentOrder) {
                return response()->json([
                    'status' => false,
                    'message' => 'Payment order not found'
                ], 404);
            }

            if ($paymentOrder->status === 'paid') {
                return response()->json([
                    'status' => false,
                    'message' => 'Payment already processed'
                ], 400);
            }

            // Get Razorpay credentials
            $razorpay = PaymentGateway::where('name', 'Razorpay')
                ->where('is_active', 1)
                ->first();

            if (!$razorpay) {
                return response()->json([
                    'status' => false,
                    'message' => 'Razorpay gateway not found'
                ], 400);
            }

            $credentials = json_decode($razorpay->config, true);
            $api = new Api($credentials['key'], $credentials['secret']);

            // Fetch order from Razorpay
            $order = $api->order->fetch($orderId);
            
            \Log::info('Razorpay Order Fetched', [
                'order_id' => $orderId,
                'status' => $order->status,
                'amount_paid' => $order->amount_paid
            ]);

            // Check if order is paid
            if ($order->status === 'paid' && $order->amount_paid > 0) {
                // Fetch payments for this order
                $payments = $api->order->fetch($orderId)->payments();
                
                if ($payments && count($payments->items) > 0) {
                    $payment = $payments->items[0];
                    
                    if ($payment->status === 'captured') {
                        DB::beginTransaction();

                        // Update wallet balance
                        $wallet = $paymentOrder->adWallet;
                        if ($wallet) {
                            $wallet->balance += $paymentOrder->amount;
                            $wallet->save();
                        }

                        // Update payment order
                        $paymentOrder->update([
                            'razorpay_payment_id' => $payment->id,
                            'status' => 'paid',
                            'paid_at' => now()
                        ]);

                        // Create transaction record
                        AdTransaction::create([
                            'ad_wallet_id' => $paymentOrder->ad_wallet_id,
                            'amount' => $paymentOrder->amount,
                            'is_commission' => 0,
                            'type' => 'credit',
                            'transaction_id' => $payment->id,
                            'purpose' => 'recharge',
                            'note' => 'Manual Processing - Order: ' . $orderId
                        ]);

                        DB::commit();

                        return response()->json([
                            'status' => true,
                            'message' => 'Payment processed successfully',
                            'amount' => $paymentOrder->amount,
                            'payment_id' => $payment->id
                        ]);
                    }
                }
            }

            return response()->json([
                'status' => false,
                'message' => 'Order is not paid or payment not captured',
                'order_status' => $order->status,
                'amount_paid' => $order->amount_paid
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Process Pending Payment Error', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}

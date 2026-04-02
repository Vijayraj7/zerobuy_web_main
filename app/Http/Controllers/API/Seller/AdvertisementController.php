<?php

namespace App\Http\Controllers\API\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdWalletResource;
use App\Http\Resources\Seller\AdvertisementResource;
use App\Http\Resources\SellerProductResource;
use App\Http\Resources\ShopResource;
use App\Models\AdvertisementSetting;
use App\Models\AdWallet;
use App\Models\AdPaymentOrder;
use App\Models\AdTransaction;
use App\Models\PaymentGateway;
use App\Models\Product;
// use App\Models\Wallet;
use App\Repositories\AdvertisementRepository;
use App\Http\Resources\WalletResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;

class AdvertisementController extends Controller
{
    /**
     * Get all advertisements
     */
    public function index()
    {
        $shop = generaleSetting('shop');
        $advertisements = AdvertisementRepository::query()->where('shop_id', $shop->id)->get();

        $wallet = AdWallet::where('user_id', $shop->user_id)->first();

        $products = Product::where('shop_id', $shop->id)->get();

        $setting = AdvertisementSetting::first();

        return $this->json('all advertisements', [
            'daily_budget' => (float) $setting->daily_budget,
            'wallet' => AdWalletResource::make($wallet),
            'shop' => ShopResource::make($shop),
            'advertisements' => AdvertisementResource::collection($advertisements),
            'products' => SellerProductResource::collection($products),
        ]);
    }

    public function store(Request $request)
    {
        $advertisement = AdvertisementRepository::create([
            'shop_id' => $request->shop_id,
            'title' => $request->title,
            'description' => $request->description,
            'image' => $request->image,
            'link' => $request->link,
            'status' => $request->status,
        ]);

        return $this->json('advertisement created', [
            'advertisement' => new AdvertisementResource($advertisement),
        ]);
    }

    public function createWalletOrder(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:1']);

        $shop = generaleSetting('shop');

        $razorpay = PaymentGateway::where('name', 'Razorpay')->where('is_active', 1)->first();
        if (!$razorpay) {
            return response()->json(['status' => false, 'message' => 'Razorpay payment gateway is not configured or inactive'], 400);
        }

        $credentials = json_decode($razorpay->config, true);
        $key    = $credentials['key'] ?? null;
        $secret = $credentials['secret'] ?? null;

        if (!$key || !$secret) {
            return response()->json(['status' => false, 'message' => 'Razorpay credentials are missing'], 400);
        }

        try {
            $api   = new Api($key, $secret);
            $order = $api->order->create([
                'amount'   => (int) ($request->amount * 100),
                'currency' => 'INR',
                'receipt'  => 'adwallet_' . $shop->id . '_' . time(),
                'notes'    => ['shop_id' => $shop->id, 'purpose' => 'Ad Wallet Recharge'],
            ]);

            $wallet = AdWallet::firstOrCreate(
                ['user_id' => $shop->user_id],
                ['balance' => 0]
            );

            AdPaymentOrder::create([
                'user_id'           => $shop->user_id,
                'ad_wallet_id'      => $wallet->id,
                'razorpay_order_id' => $order->id,
                'amount'            => $request->amount,
                'currency'          => $order->currency,
                'receipt'           => $order->receipt,
                'status'            => 'created',
                'notes'             => json_encode(['shop_id' => $shop->id, 'purpose' => 'Ad Wallet Recharge']),
            ]);

            return response()->json([
                'status'       => true,
                'order'        => [
                    'id'       => $order->id,
                    'amount'   => $order->amount,
                    'currency' => $order->currency,
                ],
                'razorpay_key' => $key,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to create order: ' . $e->getMessage()], 500);
        }
    }

    public function verifyWalletPayment(Request $request)
    {
        $request->validate([
            'razorpay_payment_id' => 'required',
            'razorpay_order_id'   => 'required',
            'razorpay_signature'  => 'required',
            'amount'              => 'required|numeric|min:1',
        ]);

        $shop = generaleSetting('shop');

        $razorpay = PaymentGateway::where('name', 'Razorpay')->where('is_active', 1)->first();
        if (!$razorpay) {
            return response()->json(['status' => false, 'message' => 'Razorpay payment gateway not found'], 400);
        }

        $credentials = json_decode($razorpay->config, true);
        $key    = $credentials['key'] ?? null;
        $secret = $credentials['secret'] ?? null;

        if (!$key || !$secret) {
            return response()->json(['status' => false, 'message' => 'Razorpay secret key is missing'], 400);
        }

        try {
            $api = new Api($key, $secret);
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature'  => $request->razorpay_signature,
            ]);

            DB::beginTransaction();

            $paymentOrder = AdPaymentOrder::where('razorpay_order_id', $request->razorpay_order_id)->first();
            if (!$paymentOrder) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Payment order not found'], 404);
            }

            if ($paymentOrder->status === 'paid') {
                DB::rollBack();
                return response()->json(['status' => true, 'message' => '₹' . number_format($request->amount, 2) . ' already added to your ad wallet.']);
            }

            $wallet = AdWallet::where('user_id', $shop->user_id)->first();
            $wallet->balance += $request->amount;
            $wallet->save();

            $paymentOrder->update([
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature'  => $request->razorpay_signature,
                'status'              => 'paid',
                'paid_at'             => now(),
            ]);

            AdTransaction::create([
                'ad_wallet_id'   => $wallet->id,
                'amount'         => $request->amount,
                'is_commission'  => 0,
                'type'           => 'credit',
                'transaction_id' => $request->razorpay_payment_id,
                'purpose'        => 'Recharge',
                'note'           => 'Razorpay Payment - Order: ' . $request->razorpay_order_id,
            ]);

            DB::commit();

            return response()->json(['status' => true, 'message' => '₹' . number_format($request->amount, 2) . ' added to your ad wallet successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Payment verification failed: ' . $e->getMessage()], 400);
        }
    }
}

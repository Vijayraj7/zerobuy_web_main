<?php

namespace App\Http\Controllers\API\Seller;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionPurchaseRequest;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\SubscriptionPaymentOrder;
use App\Repositories\ShopSubscriptionRepository;
use App\Repositories\SubscriptionPlanRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;

class SubscriptionController extends Controller
{
    public function index()
    {
        $generalSettings = generaleSetting('setting');
        $shop = generaleSetting('shop');

        if ($generalSettings?->business_based_on != 'subscription') {
            abort(404);
        }

        $subscriptionPlans = SubscriptionPlanRepository::query()->active()->paginate(20);
        $paymentGateways = Cache::rememberForever('payment_gateway', function () {
            return PaymentGateway::where('is_active', true)->get();
        });

        return $this->json('subscription lists', [
            'current_subscription' => $shop->currentSubscription,
            'subscription' => $subscriptionPlans,
            'payments' => $paymentGateways
        ]);
    }

    /**
     * Step 1: Create a Razorpay order for subscription purchase.
     * Called from Flutter app before opening Razorpay checkout.
     */
    public function createOrder(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $shop = generaleSetting('shop');
        $subscriptionPlan = SubscriptionPlanRepository::find($request->plan_id);

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

            $amount = $subscriptionPlan->price * 100; // Convert to paise

            $order = $api->order->create([
                'amount' => $amount,
                'currency' => 'INR',
                'receipt' => 'sub_' . $shop->id . '_' . time(),
                'notes' => [
                    'shop_id' => $shop->id,
                    'user_id' => $shop->user_id,
                    'plan_id' => $subscriptionPlan->id,
                    'plan_name' => $subscriptionPlan->name,
                    'purpose' => 'Subscription Purchase',
                ]
            ]);

            \Log::info('Razorpay Subscription Order Created', [
                'order_id' => $order->id,
                'amount' => $order->amount,
                'plan_id' => $subscriptionPlan->id,
            ]);

            // Create a pending payment record
            $payment = Payment::create([
                'amount' => $subscriptionPlan->price,
                'payment_method' => 'razorpay',
                'is_paid' => false,
            ]);

            // Create a pending subscription record
            $subscription = ShopSubscriptionRepository::create([
                'shop_id' => $shop->id,
                'plan_id' => $subscriptionPlan->id,
                'price' => $subscriptionPlan->price,
                'duration' => $subscriptionPlan->duration,
                'sale_limit' => $subscriptionPlan->sale_limit,
                'remaining_sales' => $subscriptionPlan->sale_limit,
                'payment_id' => $payment->id,
                'status' => SubscriptionStatus::PENDING,
            ]);

            // Save Razorpay order to track payment
            SubscriptionPaymentOrder::create([
                'user_id' => $shop->user_id,
                'shop_id' => $shop->id,
                'plan_id' => $subscriptionPlan->id,
                'payment_id' => $payment->id,
                'subscription_id' => $subscription->id,
                'razorpay_order_id' => $order->id,
                'amount' => $subscriptionPlan->price,
                'currency' => $order->currency,
                'receipt' => $order->receipt,
                'status' => 'created',
                'notes' => json_encode([
                    'shop_id' => $shop->id,
                    'plan_name' => $subscriptionPlan->name,
                    'purpose' => 'Subscription Purchase',
                ]),
            ]);

            return response()->json([
                'message' => 'Razorpay order created successfully',
                'status' => true,
                'order' => [
                    'id' => $order->id,
                    'amount' => $order->amount,
                    'currency' => $order->currency,
                    'receipt' => $order->receipt,
                ],
                'razorpay_key' => $key,
                'plan' => [
                    'id' => $subscriptionPlan->id,
                    'name' => $subscriptionPlan->name,
                    'price' => $subscriptionPlan->price,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Razorpay Subscription Order Creation Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Step 2: Verify Razorpay payment after checkout completes in Flutter app.
     */
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'razorpay_payment_id' => 'required',
            'razorpay_order_id' => 'required',
            'razorpay_signature' => 'required',
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
        $key = $credentials['key'] ?? null;
        $secret = $credentials['secret'] ?? null;

        if (!$key || !$secret) {
            return response()->json([
                'status' => false,
                'message' => 'Razorpay credentials are missing'
            ], 400);
        }

        try {
            // Verify signature
            $api = new Api($key, $secret);

            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ];

            $api->utility->verifyPaymentSignature($attributes);

            DB::beginTransaction();

            // Find payment order
            $paymentOrder = SubscriptionPaymentOrder::where('razorpay_order_id', $request->razorpay_order_id)->first();

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
                    'status' => true,
                    'message' => 'Subscription already activated'
                ]);
            }

            // Activate the subscription
            $this->activateSubscription($paymentOrder, $request->razorpay_payment_id, $request->razorpay_signature);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Subscription activated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Razorpay Subscription Payment Verification Failed', [
                'error' => $e->getMessage(),
                'order_id' => $request->razorpay_order_id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Payment verification failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Razorpay Webhook handler for subscription payments.
     * Catches payment.captured events as a fallback for missed client-side verifications.
     * This route should be outside auth middleware.
     */
    public function webhookHandler(Request $request)
    {
        \Log::debug('Subscription Webhook received');
        \Log::debug('Subscription Webhook payload: ' . json_encode($request->all()));

        $post = file_get_contents('php://input');
        $data = json_decode($post, true);

        if (isset($data['event']) && $data['event'] === 'payment.captured') {
            try {
                $status = $data['payload']['payment']['entity']['status'];
                $raz_order_id = $data['payload']['payment']['entity']['order_id'];
                $raz_payment_id = $data['payload']['payment']['entity']['id'];

                // Find payment order that is not already processed
                $paymentOrder = SubscriptionPaymentOrder::where('razorpay_order_id', $raz_order_id)
                    ->where('status', 'created')
                    ->first();

                if ($status === 'captured') {
                    if ($paymentOrder !== null) {
                        DB::beginTransaction();

                        $this->activateSubscription($paymentOrder, $raz_payment_id);

                        DB::commit();

                        \Log::debug('Subscription webhook success: ' . $raz_order_id);
                        echo 200;
                    } else {
                        \Log::debug('Subscription payment order not found or already processed: ' . $raz_order_id);
                        echo 200; // Return success to avoid retries
                    }
                } else {
                    \Log::debug('Invalid payment status: ' . $status . ' for subscription order: ' . $raz_order_id);
                    echo 400;
                }
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Subscription Webhook Exception: ' . $e->getMessage());
                echo 500;
            }
        } else {
            \Log::debug('Subscription Webhook: Invalid or missing event: ' . json_encode($data));
            echo 400;
        }
    }

    /**
     * Process a pending subscription payment manually (for recovery).
     */
    public function processPendingPayment($orderId)
    {
        try {
            $paymentOrder = SubscriptionPaymentOrder::where('razorpay_order_id', $orderId)->first();

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

            \Log::info('Razorpay Subscription Order Fetched', [
                'order_id' => $orderId,
                'status' => $order->status,
                'amount_paid' => $order->amount_paid,
            ]);

            if ($order->status === 'paid' && $order->amount_paid > 0) {
                $payments = $api->order->fetch($orderId)->payments();

                if ($payments && count($payments->items) > 0) {
                    $payment = $payments->items[0];

                    if ($payment->status === 'captured') {
                        DB::beginTransaction();

                        $this->activateSubscription($paymentOrder, $payment->id);

                        DB::commit();

                        return response()->json([
                            'status' => true,
                            'message' => 'Subscription payment processed successfully',
                            'payment_id' => $payment->id,
                        ]);
                    }
                }
            }

            return response()->json([
                'status' => false,
                'message' => 'Order is not paid or payment not captured',
                'order_status' => $order->status,
                'amount_paid' => $order->amount_paid,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Process Pending Subscription Payment Error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Shared logic to activate a subscription after successful payment.
     */
    private function activateSubscription(SubscriptionPaymentOrder $paymentOrder, string $razorpayPaymentId, ?string $razorpaySignature = null)
    {
        // Mark payment as paid
        $payment = Payment::find($paymentOrder->payment_id);
        if ($payment) {
            $payment->update(['is_paid' => true]);
        }

        // Get the pending subscription
        $subscription = ShopSubscriptionRepository::find($paymentOrder->subscription_id);

        if (!$subscription) {
            throw new \Exception('Subscription record not found for payment order: ' . $paymentOrder->id);
        }

        // Cancel any existing active subscription & carry over remaining sales
        $currentSubscription = ShopSubscriptionRepository::query()
            ->where('shop_id', $paymentOrder->shop_id)
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->where('id', '!=', $subscription->id)
            ->first();

        $saleLimit = $subscription->sale_limit;
        $remainingSales = $subscription->sale_limit;
        $extraDays = 0;

        if ($currentSubscription) {
            // Carry over remaining sales
            if ($currentSubscription->remaining_sales) {
                if ($saleLimit !== null) {
                    $saleLimit = $saleLimit + $currentSubscription->remaining_sales;
                }
                $remainingSales = ($remainingSales ?? 0) + $currentSubscription->remaining_sales;
            }

            // Carry over remaining days from current subscription
            if ($currentSubscription->ends_at && $currentSubscription->ends_at->gt(now())) {
                $extraDays = (int) now()->diffInDays($currentSubscription->ends_at);
            }

            $currentSubscription->update([
                'status' => SubscriptionStatus::CANCELLED,
            ]);
        }

        // Calculate total duration: new plan days + remaining days from old plan
        $totalDays = $subscription->duration ? (int) $subscription->duration + $extraDays : null;

        // Activate the new subscription
        $subscription->update([
            'starts_at' => now(),
            'ends_at' => $totalDays ? now()->addDays($totalDays) : null,
            'sale_limit' => $saleLimit,
            'remaining_sales' => $remainingSales,
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        // Update Razorpay payment order record
        $updateData = [
            'razorpay_payment_id' => $razorpayPaymentId,
            'status' => 'paid',
            'paid_at' => now(),
        ];

        if ($razorpaySignature) {
            $updateData['razorpay_signature'] = $razorpaySignature;
        }

        $paymentOrder->update($updateData);
    }

    /**
     * Legacy purchase method — kept for backward compatibility.
     * Immediately activates without server-side payment verification.
     */
    public function purchase(SubscriptionPurchaseRequest $request)
    {
        $subscriptionPlan = SubscriptionPlanRepository::find($request->plan_id);
        $result = ShopSubscriptionRepository::storeByRequest($request, $subscriptionPlan);

        $subscription = $result['subscription'];

        $payment = $result['payment'];

        $payment->update([
            'is_paid' => true,
        ]);

        $currentSubscription = ShopSubscriptionRepository::query()
            ->where('shop_id', $subscription->shop_id)
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->first();

        $saleLimit = $subscription->sale_limit;
        $remainingSales = $subscription->sale_limit;
        $extraDays = 0;

        if ($currentSubscription) {
            // Carry over remaining sales
            if ($currentSubscription->remaining_sales) {
                if ($saleLimit !== null) {
                    $saleLimit = $saleLimit + $currentSubscription->remaining_sales;
                }
                $remainingSales = ($remainingSales ?? 0) + $currentSubscription->remaining_sales;
            }

            // Carry over remaining days from current subscription
            if ($currentSubscription->ends_at && $currentSubscription->ends_at->gt(now())) {
                $extraDays = (int) now()->diffInDays($currentSubscription->ends_at);
            }

            $currentSubscription->update([
                'status' => SubscriptionStatus::CANCELLED,
            ]);
        }

        // Calculate total duration: new plan days + remaining days from old plan
        $totalDays = $subscription->duration ? (int) $subscription->duration + $extraDays : null;

        $subscription->update([
            'starts_at' => now(),
            'ends_at' => $totalDays ? now()->addDays($totalDays) : null,
            'sale_limit' => $saleLimit,
            'remaining_sales' => $remainingSales,
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        return $this->json('Subscription created', [
            'subscription' => $result,
        ]);
    }
}

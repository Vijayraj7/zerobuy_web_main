<?php

namespace App\Http\Controllers\Gateway;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentGatewayController extends Controller
{
    /**
     * Payment gateway
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     */
    public function payment(Payment $payment, Request $request)
    {
        $gateway = $request->gateway;

        if ($payment->is_paid) {
            return to_route('order.payment.cancel', ['payment' => $payment, 'error' => 'Order already paid']);
        }

        $paymentGateway = PaymentGateway::where('name', $gateway)->first();

        if (! $paymentGateway || ! $paymentGateway->is_active) {
            $message = $paymentGateway ? 'Payment gateway not active' : 'Payment gateway not found';

            return to_route('order.payment.cancel', ['payment' => $payment, 'error' => $message]);
        }

        $dirName = $paymentGateway->alias;

        $controller = __NAMESPACE__.'\\'.$dirName.'\\ProcessController';

        $url = $controller::process($paymentGateway, $payment);

        $error = json_decode($url);
        if ($error) {
            $error = $error->error ?? 'Payment gateway error occurred not configured correctly';

            return to_route('order.payment.cancel', ['payment' => $payment, 'error' => $error]);
        }

        return redirect()->away($url);
    }

    /**
     * Payment success
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function success(Request $request, Payment $payment)
    {
        if ($payment->is_paid) {
            return to_route('order.payment.success', $payment);
        }

        try {
            DB::transaction(function () use ($payment, $request) {
                if ($payment->orders()->count() === 0 && strtolower((string) $payment->payment_method) === 'cashfree') {
                    $intent = Cache::get('payment_intent_cashfree:'.$payment->id);

                    if (! is_array($intent)) {
                        throw new \RuntimeException('Payment session expired. Please place order again.');
                    }

                    $user = User::find((int) ($intent['user_id'] ?? 0));
                    if (! $user || ! $user->customer) {
                        throw new \RuntimeException('Invalid payment session user.');
                    }

                    $shopIds = array_map('intval', (array) ($intent['shop_ids'] ?? []));
                    $isBuyNow = (bool) ($intent['is_buy_now'] ?? false);

                    $carts = $user->customer->carts()
                        ->whereIn('shop_id', $shopIds)
                        ->where('is_buy_now', $isBuyNow)
                        ->get();

                    if ($carts->isEmpty()) {
                        throw new \RuntimeException('Cart is empty for this payment session.');
                    }

                    $orderRequest = new Request();
                    $orderRequest->merge([
                        'address_id' => (int) ($intent['address_id'] ?? 0),
                        'coupon_code' => $intent['coupon_code'] ?? null,
                        'note' => $intent['note'] ?? null,
                        'payment_method' => $intent['payment_method'] ?? 'cashfree',
                        'shop_ids' => $shopIds,
                        'is_buy_now' => $isBuyNow,
                        'gst' => $intent['gst'] ?? null,
                        'payment_user_id' => $user->id,
                    ]);

                    OrderRepository::storeByRequestFromCart(
                        $orderRequest,
                        \App\Enums\PaymentMethod::CASHFREE,
                        $carts,
                        $payment,
                    );
                }

                $payment->orders()->update([
                    'payment_status' => PaymentStatus::PAID->value,
                ]);

                $payment->update([
                    'is_paid' => true,
                ]);
            });

            Cache::forget('payment_intent_cashfree:'.$payment->id);
        } catch (\Throwable $e) {
            Log::error('Cashfree payment success handling failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return to_route('order.payment.cancel', [
                'payment' => $payment,
                'error' => $e->getMessage(),
            ]);
        }

        return to_route('order.payment.success', $payment);
    }

    /**
     * Payment cancel
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(Payment $payment)
    {
        return to_route('order.payment.cancel', $payment);
    }

    /**
     * Payment success response show
     *
     * @return \Illuminate\Http\JsonResponse1`
     */
    public function paymentSuccess(Payment $payment)
    {
        return view('payment.success', compact('payment'));
    }

    /**
     * Payment cancel response show
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentCancel(Payment $payment, Request $request)
    {
        return view('payment.fail', compact('payment', 'request'));

        // return $this->json($request->error ?? 'Order payment cancelled', [
        //     'payment' => [
        //         'payment_status' => $payment->is_paid ? 'Paid' : 'Pending',
        //         'payment_method' => $payment->payment_method,
        //         'amount' => $payment->amount,
        //         'total_orders' => $payment->orders->count(),
        //     ],
        // ], 422);
    }
}

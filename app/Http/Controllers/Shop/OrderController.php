<?php

namespace App\Http\Controllers\Shop;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use App\Models\OrderStatusTimeline;
use App\Repositories\NotificationRepository;
use App\Repositories\OrderRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Services\NotificationServices;
use Carbon\Carbon;
use Endroid\QrCode\QrCode as EndroidQrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class OrderController extends Controller
{
    /**
     * Display the order list with filter status.
     */
    public function index($status = null)
    {
        $status = $status ? str_replace('_', ' ', $status) : '';

        return view('shop.order.index', compact('status'));
    }

    public function apiIndex(Request $request)
    {
        $status = $request->get('status');
        $shop = generaleSetting('shop');
        $shopId = $shop?->id;

        if (! $shopId) {
            return $this->json('Order list', ['orders' => []]);
        }

        $orders = Order::query()
            ->where('shop_id', $shopId)
            ->with([
                'customer.user:id,name,phone',
                'orderProducts:id,order_id,product_id,quantity',
                'orderProducts.product.media:id,src',
            ])
            ->when($status, function ($query) use ($status) {
                $query->where('order_status', $status);
            })
            ->latest('id')
            ->get();

        $data = $orders->map(function ($order) {
            $firstProduct = $order->orderProducts?->first()?->product;
            $thumbnail = $firstProduct?->thumbnail ?? asset('default/default.jpg');

            return [
                'id' => $order->id,
                'thumbnail' => $thumbnail,
                'created_at' => $order->created_at?->format('d-m-Y | h:i A') ?? '-',
                'order_id' => ($order->prefix ?? '') . ($order->order_code ?? ''),
                'customer_name' => $order->customer?->user?->name ?? '-',
                'mobile_no' => $order->customer?->user?->phone ?? '-',
                'quantity' => $order->orderProducts?->sum('quantity') ?? 0,
                'total_amount' => showCurrency($order->payable_amount ?? 0),
                'payment_method' => __($order->payment_method?->value ?? '-'),
                'status' => __($order->order_status?->value ?? '-'),
                'details_url' => route('shop.order.show', $order->id),
                'invoice_url' => route('shop.download-invoice', $order->id),
            ];
        })->values();

        return $this->json('Order list', ['orders' => $data]);
    }

    /**
     * Display the order details.
     */
    public function show($orderId)
    {
        // $order = Order::whereId($orderId)->firstOrFail(); 

        $order = Order::whereId($orderId)->firstOrFail()->load('address.stateData', 'address.districtData', 'shop.deliverySetting');


        $orderStatus = OrderStatus::cases();

        $riders = Driver::whereHas('user', function ($query) {
            $query->where('is_active', true);
        })->get();

        $deliverySetting = $order->shop->deliverySetting;
        $isManualDelivery = $deliverySetting && $deliverySetting->delivery_mode === 'manual';

        return view('shop.order.show', compact('order', 'orderStatus', 'riders', 'isManualDelivery'));
    }

    /**
     * Update the order status.
     */
    public function statusChange(Order $order, Request $request)
    {
        $request->validate(['status' => 'required']);

        $order->update(['order_status' => $request->status]);

        OrderStatusTimeline::updateOrCreate(
            [
                'order_id' => $order->id,
                'status' => $request->status,
            ],
            [
                'changed_at' => Carbon::now(),
            ]
        );

        if ($request->status == OrderStatus::DELIVERED->value) {
            $this->updateWalletAndTransaction($order);
        }

        if ($request->status == OrderStatus::CANCELLED->value) {
            foreach ($order->products as $product) {

                $qty = $product->pivot->quantity;

                $product->update(['quantity' => $product->quantity + $qty]);

                $flashSale = $product->flashSales?->first();
                $flashSaleProduct = null;

                if ($flashSale) {
                    $flashSaleProduct = $flashSale?->products()->where('id', $product->id)->first();

                    if ($flashSaleProduct && $product->pivot?->price) {
                        if ($flashSaleProduct->pivot->sale_quantity >= $qty && ($product->pivot?->price == $flashSaleProduct->pivot->price)) {
                            $flashSale->products()->updateExistingPivot($product->id, [
                                'sale_quantity' => $flashSaleProduct->pivot->sale_quantity - $qty,
                            ]);
                        }
                    }
                }
            }
        }

        $title = 'Order status ' . $request->status;
        $message = 'Your order ' . $request->status . ' order id: #' . $order->order_code;
        $deviceKeys = $order->customer->user->devices->pluck('key')->toArray();

        $noty = null;
        try {
            $noty =  NotificationServices::sendNotification($message, $deviceKeys, $title);
        } catch (\Throwable $th) {
        }

        return back()->with('success', __('Order status updated successfully.'));
    }

    /**
     * Update track URL and delivery charge for manual delivery.
     */
    public function updateTrackingAndCharge(Order $order, Request $request)
    {
        $request->validate([
            'track_url' => 'nullable|url|max:500',
            'delivery_charge' => 'nullable|numeric|min:0',
        ]);

        $updateData = [];

        if ($request->filled('track_url')) {
            $updateData['track_url'] = $request->track_url;
        }

        // Only allow updating delivery_charge if manual delivery or current delivery_charge == 0
        $deliverySetting = $order->shop->deliverySetting;
        $isManualDelivery = $deliverySetting && $deliverySetting->delivery_mode === 'manual';
        if ($request->filled('delivery_charge')) {
            if ($isManualDelivery || $order->delivery_charge == 0) {
                $oldDeliveryCharge = $order->delivery_charge;
                $newDeliveryCharge = $request->delivery_charge;
                $updateData['delivery_charge'] = $newDeliveryCharge;
                // Recalculate payable amount
                $updateData['payable_amount'] = $order->payable_amount - $oldDeliveryCharge + $newDeliveryCharge;
            }
        }

        if (!empty($updateData)) {
            $order->update($updateData);
            return back()->with('success', __('Order tracking and charges updated successfully.'));
        }

        return back()->with('info', __('No changes made.'));
    }

    /**
     * Update the payment status.
     */
    public function paymentStatusToggle(Order $order)
    {
        if ($order->payment_status->value == PaymentStatus::PAID->value) {
            return back()->with('error', __('When order is paid, payment status cannot be changed.'));
        }
        $order->update(['payment_status' => PaymentStatus::PAID->value]);

        return back()->with('success', __('Payment status updated successfully'));
    }

    public function downloadInvoice($id)
    {
        $order = Order::findOrFail($id);

        $orderCode = '#' . $order->prefix . $order->order_code;

        $qrCode = new EndroidQrCode($orderCode);
        $qrCode->setSize(100);

        $writer = new PngWriter;
        $qrCodeImage = $writer->write($qrCode)->getDataUri();

        // pdf config
        $defaultConfig = (new ConfigVariables)->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables)->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $fontData['kalpurush'] = [
            'R' => 'kalpurush.ttf',
        ];

        $paperSize = 'A4';

        $mPdf = new Mpdf([
            'mode' => 'UTF-8',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => storage_path('app/public/mpdf_tmp'),
            'fontDir' => array_merge($fontDirs, [public_path('fonts')]),
            'fontdata' => $fontData,
            'format' => $paperSize,
        ]);

        $view = view('PDF.invoice', compact('order', 'qrCodeImage'))->render();
        $mPdf->WriteHTML($view);

        // Output the PDF as a download
        return $mPdf->Output('invoice-' . $order->prefix . $order->order_code . '.pdf', 'D');

        // Output the PDF as a stream
        // return $mPdf->Output('invoice-' . $order->prefix . $order->order_code . '.pdf', 'I');
    }

    public function paymentSlip($id)
    {
        $order = Order::findOrFail($id);

        // pdf config
        $defaultConfig = (new ConfigVariables)->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables)->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $fontData['kalpurush'] = [
            'R' => 'kalpurush.ttf',
        ];

        $paperSize = 'A4';

        $mPdf = new Mpdf([
            'mode' => 'UTF-8',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => storage_path('app/public/mpdf_tmp'),
            'fontDir' => array_merge($fontDirs, [public_path('fonts')]),
            'fontdata' => $fontData,
            'format' => $paperSize,
        ]);

        $view = view('PDF.payment-slip', compact('order'))->render();
        $mPdf->WriteHTML($view);

        $pdfContent = $mPdf->Output('payment-slip-' . $order->prefix . $order->order_code . '.pdf', 'S');

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="payment-slip-' . $order->prefix . $order->order_code . '.pdf"',
        ]);
    }

    private function updateWalletAndTransaction($order)
    {

        $generaleSetting = generaleSetting('setting');

        $commission = 0;

        if ($generaleSetting?->commission_charge != 'monthly') {

            if ($generaleSetting?->commission_type != 'fixed') {
                $commission = $order->total_amount * $generaleSetting->commission / 100;
            } else {
                $commission = $generaleSetting->commission ?? 0;
            }
        }

        $order->update([
            'delivery_date' => now(),
            'delivered_at' => now(),
            'payment_status' => PaymentStatus::PAID->value,
            'admin_commission' => $commission,
        ]);

        $wallet = $order->shop->user->wallet;

        WalletRepository::updateByRequest($wallet, $order->payable_amount, 'credit');

        TransactionRepository::storeByRequest($wallet, $commission, 'debit', true, true, 'admin commission', 'order', $order->id, null);
    }
}

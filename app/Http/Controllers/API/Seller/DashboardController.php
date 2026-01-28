<?php

namespace App\Http\Controllers\API\Seller;

use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\WithdrawResource;
use App\Repositories\ShopSubscriptionRepository;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $filterType = request()->filter_type ?? 'this_year';

        $shop = generaleSetting('shop');
        $orderObject = $shop->orders();

        $totalSales = (clone $orderObject)->where(function ($query) {
            $query->where('order_status', OrderStatus::DELIVERED->value)->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        })->sum('total_amount');

        $todayOrders = (clone $orderObject)->whereDate('created_at', Carbon::today())->count();

        $pendingOrder = (clone $orderObject)->where('order_status', OrderStatus::PENDING->value)->count();

        $toPickupOrders = (clone $orderObject)->where(function ($query) {
            $query->whereHas('driverOrder')->where('order_status', OrderStatus::CONFIRM->value)->orWhere('order_status', OrderStatus::PENDING->value);
            // ->orWhere('order_status', OrderStatus::PICKUP->value);
        })->count();

        $toDeliveryOrders = (clone $orderObject)->where('order_status', OrderStatus::SHIPPED->value)->count();

        $pendingWithdraw = $shop->withdraws()->where(function ($query) {
            $query->where('status', 'pending');
        })->sum('amount');

        $walletBalance = auth()->user()->wallet->balance - $pendingWithdraw;
        $walletBalance = $walletBalance > 0 ? $walletBalance : 0;

        $latestPendingWithdraw = $shop->withdraws()->where(function ($query) {
            $query->where('status', 'pending');
        })->latest('id')->first();

        if ($filterType === 'last_year') {
            $startDate = now()->subYear()->startOfYear();
            $endDate = now()->subYear()->endOfYear();
        } else {
            $startDate = now()->startOfYear();
            $endDate = now()->endOfYear();
        }

        // Get monthly sale chart
        $monthList = [];
        $valueList = [];

        for ($i = 1; $i <= 12; $i++) {
            $month = Carbon::create(null, $i, 1);

            $totalAmount = (clone $orderObject)->where(function ($query) use ($month, $startDate, $endDate) {
                $query->where('order_status', OrderStatus::DELIVERED->value)->whereBetween('created_at', [$month->startOfMonth()->format('Y-m-d'), $month->endOfMonth()->format('Y-m-d')])->whereBetween('created_at', [$startDate, $endDate]);
            })->sum('total_amount');

            $monthList[] = $month->format('M');
            $valueList[] = (float) $totalAmount;
        }

        $maxAmount = max($valueList);
        $minAmount = min($valueList);

        return $this->json('Seller dashboard data', [
            'pending_order' => $pendingOrder,
            'to_pickup_order' => $toPickupOrders,
            'today_order' => $todayOrders,
            'to_delivery_order' => $toDeliveryOrders,
            'this_manth_sales' => number_format($totalSales, 2, '.', ','),
            'wallet_balance' => number_format($walletBalance, 2, '.', ','),
            'pending_withdraw' => $latestPendingWithdraw ? WithdrawResource::make($latestPendingWithdraw) : null,
            'max_chart_amount' => (float) number_format($maxAmount, 2, '.', ''),
            'min_chart_amount' => (float) number_format($minAmount, 2, '.', ''),
            'sales_chart_months' => $monthList,
            'sales_chart_values' => $valueList,
        ]);
    }

    public function summary()
    {
        $filterType = request()->filter_type ?? 'this_year';

        $shop = generaleSetting('shop');
        $orderObject = $shop->orders();

        $totalSales = (clone $orderObject)->where(function ($query) {
            $query->where('order_status', OrderStatus::DELIVERED->value)->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        })->sum('total_amount');

        $todayOrders = (clone $orderObject)->whereDate('created_at', Carbon::today())->count();

        $pendingOrder = (clone $orderObject)->where('order_status', OrderStatus::PENDING->value)->count();

        $toPickupOrders = (clone $orderObject)->where(function ($query) {
            $query->whereHas('driverOrder')->where('order_status', OrderStatus::CONFIRM->value)->orWhere('order_status', OrderStatus::PENDING->value);
        })->count();

        $toDeliveryOrders = (clone $orderObject)->where('order_status', OrderStatus::SHIPPED->value)->count();

        $pendingWithdraw = $shop->withdraws()->where(function ($query) {
            $query->where('status', 'pending');
        })->sum('amount');

        $walletBalance = auth()->user()->wallet->balance - $pendingWithdraw;
        $walletBalance = $walletBalance > 0 ? $walletBalance : 0;

        $latestPendingWithdraw = $shop->withdraws()->where(function ($query) {
            $query->where('status', 'pending');
        })->latest('id')->first();

        if ($filterType === 'last_year') {
            $startDate = now()->subYear()->startOfYear();
            $endDate = now()->subYear()->endOfYear();
        } else {
            $startDate = now()->startOfYear();
            $endDate = now()->endOfYear();
        }

        $monthList = [];
        $valueList = [];

        for ($i = 1; $i <= 12; $i++) {
            $month = Carbon::create(null, $i, 1);

            $totalAmount = (clone $orderObject)->where(function ($query) use ($month, $startDate, $endDate) {
                $query->where('order_status', OrderStatus::DELIVERED->value)->whereBetween('created_at', [$month->startOfMonth()->format('Y-m-d'), $month->endOfMonth()->format('Y-m-d')])->whereBetween('created_at', [$startDate, $endDate]);
            })->sum('total_amount');

            $monthList[] = $month->format('M');
            $valueList[] = (float) $totalAmount;
        }

        $maxAmount = max($valueList);
        $minAmount = min($valueList);

        $statuses = $shop->orders()
            ->selectRaw('
                COUNT(CASE WHEN order_status = ? THEN 1 END) as pending,
                COUNT(CASE WHEN order_status = ? THEN 1 END) as confirm,
                COUNT(CASE WHEN order_status IN (?, ?) AND EXISTS (
                    SELECT 1 FROM driver_orders WHERE driver_orders.order_id = orders.id
                ) THEN 1 END) as toPickup,
                COUNT(CASE WHEN order_status IN (?, ?) THEN 1 END) as toDelivery,
                COUNT(CASE WHEN order_status = ? THEN 1 END) as delivered
            ', [
                OrderStatus::PENDING->value,
                OrderStatus::CONFIRM->value,
                OrderStatus::CONFIRM->value,
                OrderStatus::PENDING->value,
                OrderStatus::CONFIRM->value,
                OrderStatus::SHIPPED->value,
                OrderStatus::DELIVERED->value,
            ])->first();

        $pending = (int) $statuses->pending;
        $confirm = (int) $statuses->confirm;
        $toPickup = (int) $statuses->toPickup;
        $toDelivery = (int) $statuses->toDelivery;
        $delivered = (int) $statuses->delivered;

        $totalOrders = $shop->orders()->count();

        $statusArray = [
            (object) [
                'name' => 'All',
                'value' => $totalOrders,
                'status' => 'all',
            ],
            (object) [
                'name' => 'Pending',
                'value' => $pending,
                'status' => 'pending',
            ],
            (object) [
                'name' => 'Confirm',
                'value' => $confirm,
                'status' => 'confirm',
            ],
            (object) [
                'name' => 'To Pickup',
                'value' => $toPickup,
                'status' => 'to_pickup',
            ],
            (object) [
                'name' => 'To Delivery',
                'value' => $toDelivery,
                'status' => 'to_delivery',
            ],
            (object) [
                'name' => 'Delivered',
                'value' => $delivered,
                'status' => 'delivered',
            ],
        ];

        $totalReturns = $shop->returnOrders()->count();

        $generalSettings = generaleSetting('setting');
        $currentSubscription = null;

        if ($generalSettings?->business_based_on == 'subscription') {
            $currentSubscription = ShopSubscriptionRepository::query()
                ->with('plan')
                ->where('shop_id', $shop->id)
                ->where('status', SubscriptionStatus::ACTIVE->value)
                ->first();
        }

        return $this->json('Seller dashboard summary', [
            'dashboard' => [
                'pending_order' => $pendingOrder,
                'to_pickup_order' => $toPickupOrders,
                'today_order' => $todayOrders,
                'to_delivery_order' => $toDeliveryOrders,
                'this_manth_sales' => number_format($totalSales, 2, '.', ','),
                'wallet_balance' => number_format($walletBalance, 2, '.', ','),
                'pending_withdraw' => $latestPendingWithdraw ? WithdrawResource::make($latestPendingWithdraw) : null,
                'max_chart_amount' => (float) number_format($maxAmount, 2, '.', ''),
                'min_chart_amount' => (float) number_format($minAmount, 2, '.', ''),
                'sales_chart_months' => $monthList,
                'sales_chart_values' => $valueList,
            ],
            'orders' => [
                'total' => $totalOrders,
                'status_orders' => $statusArray,
            ],
            'returns' => [
                'total' => $totalReturns,
            ],
            'subscription' => $currentSubscription,
        ]);
    }
}

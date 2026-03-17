<?php

namespace App\Http\Controllers\Shop;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ReturnOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $shop = generaleSetting('shop');
        $shopId = $shop?->id;

        [$startDate, $endDate, $rangeLabel] = $this->resolveRange($request);

        if (! $shopId) {
            return view('shop.analytics.index', [
                'rangeLabel' => $rangeLabel,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'stats' => $this->emptyStats(),
                'statusBreakdown' => [],
                'chart' => ['labels' => [], 'sales' => [], 'returns' => []],
                'topProducts' => [],
            ]);
        }

        $ordersQuery = Order::query()
            ->where('shop_id', $shopId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $deliveredQuery = (clone $ordersQuery)->where('order_status', OrderStatus::DELIVERED->value);

        $revenue = (float) $deliveredQuery->sum('total_amount');
        $deliveredDeliveryCharge = (float) (clone $deliveredQuery)->sum('delivery_charge');
        $deliveredCount = (int) $deliveredQuery->count();
        $ordersCount = (int) $ordersQuery->count();
        $aov = $deliveredCount > 0 ? $revenue / $deliveredCount : 0;

        $returnsQuery = ReturnOrder::query()
            ->where('shop_id', $shopId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $returnsAmount = (float) $returnsQuery->sum('amount');
        $returnsCount = (int) $returnsQuery->count();

        $statusCountsRaw = Order::query()
            ->where('shop_id', $shopId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('order_status, COUNT(*) as total')
            ->groupBy('order_status')
            ->get();

        $statusCounts = $statusCountsRaw->mapWithKeys(function ($row) {
            $status = $row->order_status;
            $key = $status instanceof OrderStatus ? $status->value : (string) $status;
            return [$key => (int) $row->total];
        });

        $statusBreakdown = collect(OrderStatus::cases())->map(function ($status) use ($statusCounts) {
            return [
                'label' => $status->value,
                'count' => (int) ($statusCounts[$status->value] ?? 0),
            ];
        })->values();

        $dailySales = Order::query()
            ->where('shop_id', $shopId)
            ->where('order_status', OrderStatus::DELIVERED->value)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as day, SUM(total_amount) as total_amount')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $dailyReturns = ReturnOrder::query()
            ->where('shop_id', $shopId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as day, SUM(amount) as total_amount')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $chart = $this->buildChartSeries($startDate, $endDate, $dailySales, $dailyReturns);

        $topRows = OrderProduct::query()
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->where('orders.shop_id', $shopId)
            ->where('orders.order_status', OrderStatus::DELIVERED->value)
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->selectRaw('order_products.product_id as product_id, SUM(order_products.quantity) as total_qty, SUM(order_products.quantity * order_products.price) as total_amount')
            ->groupBy('order_products.product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $products = Product::with('media')
            ->whereIn('id', $topRows->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $topProducts = $topRows->map(function ($row) use ($products) {
            $product = $products->get($row->product_id);

            return [
                'id' => $row->product_id,
                'name' => $product?->name ?? 'Unknown',
                'image' => $product?->thumbnail ?? asset('default/default.jpg'),
                'qty' => (int) $row->total_qty,
                'amount' => (float) $row->total_amount,
            ];
        })->values();

        $topRated = Product::query()
            ->where('shop_id', $shopId)
            ->whereHas('reviews', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->with('media')
            ->withAvg([
                'reviews' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
            ], 'rating')
            ->withCount([
                'reviews' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
            ])
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->limit(5)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name ?? 'Unknown',
                    'image' => $product->thumbnail ?? asset('default/default.jpg'),
                    'rating' => (float) ($product->reviews_avg_rating ?? 0),
                    'reviews' => (int) ($product->reviews_count ?? 0),
                ];
            });

        $topCustomerRows = Order::query()
            ->where('shop_id', $shopId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('customer_id, COUNT(*) as total_orders, SUM(total_amount) as total_amount')
            ->groupBy('customer_id')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get();

        $customers = \App\Models\Customer::with('user.media')
            ->whereIn('id', $topCustomerRows->pluck('customer_id'))
            ->get()
            ->keyBy('id');

        $topCustomers = $topCustomerRows->map(function ($row) use ($customers) {
            $customer = $customers->get($row->customer_id);
            $user = $customer?->user;

            return [
                'name' => $user?->full_name ?? $user?->name ?? 'Unknown',
                'image' => $user?->thumbnail ?? asset('default/profile.jpg'),
                'mobile' => $user?->phone ?? '-',
                'orders' => (int) $row->total_orders,
                'amount' => (float) $row->total_amount,
            ];
        })->values();

        return view('shop.analytics.index', [
            'rangeLabel' => $rangeLabel,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'stats' => [
                'revenue' => $revenue,
                'delivery_charge' => $deliveredDeliveryCharge,
                'orders' => $ordersCount,
                'aov' => $aov,
                'returns_amount' => $returnsAmount,
                'returns_count' => $returnsCount,
            ],
            'statusBreakdown' => $statusBreakdown,
            'chart' => $chart,
            'topProducts' => $topProducts,
            'topRated' => $topRated,
            'topCustomers' => $topCustomers,
        ]);
    }

    private function resolveRange(Request $request): array
    {
        $period = $request->get('period', '30d');
        $startInput = $request->get('start_date');
        $endInput = $request->get('end_date');
        $now = Carbon::now();

        if ($startInput || $endInput) {
            $start = $startInput ? Carbon::parse($startInput)->startOfDay() : $now->copy()->subDays(29)->startOfDay();
            $end = $endInput ? Carbon::parse($endInput)->endOfDay() : $now->copy()->endOfDay();
            return [$start, $end, 'Custom'];
        }

        if ($period === '7d') {
            return [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay(), 'Last 7 days'];
        }

        if ($period === 'month') {
            return [$now->copy()->startOfMonth(), $now->copy()->endOfDay(), 'This month'];
        }

        if ($period === 'year') {
            return [$now->copy()->startOfYear(), $now->copy()->endOfDay(), 'This year'];
        }

        return [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay(), 'Last 30 days'];
    }

    private function buildChartSeries(Carbon $start, Carbon $end, Collection $sales, Collection $returns): array
    {
        $labels = [];
        $salesSeries = [];
        $returnsSeries = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $labels[] = $cursor->format('d M');
            $salesSeries[] = (float) ($sales[$key]->total_amount ?? 0);
            $returnsSeries[] = (float) ($returns[$key]->total_amount ?? 0);
            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'sales' => $salesSeries,
            'returns' => $returnsSeries,
        ];
    }

    private function emptyStats(): array
    {
        return [
            'revenue' => 0,
            'delivery_charge' => 0,
            'orders' => 0,
            'aov' => 0,
            'returns_amount' => 0,
            'returns_count' => 0,
        ];
    }
}

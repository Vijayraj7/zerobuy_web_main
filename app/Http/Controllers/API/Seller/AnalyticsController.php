<?php

namespace App\Http\Controllers\API\Seller;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ReturnOrder;
use App\Models\ReturnOrderDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AnalyticsController extends Controller
{
    public function summary(Request $request)
    {
        $shop = generaleSetting('shop');
        [$startDate, $endDate, $grouping] = $this->resolveDateRange($request);

        $salesByDay = Order::query()
            ->where('shop_id', $shop->id)
            ->where('order_status', OrderStatus::DELIVERED->value)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as day, SUM(total_amount) as total_amount')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $salesQtyByDay = OrderProduct::query()
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->where('orders.shop_id', $shop->id)
            ->where('orders.order_status', OrderStatus::DELIVERED->value)
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->selectRaw('DATE(orders.created_at) as day, SUM(order_products.quantity) as total_quantity')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $returnsByDay = ReturnOrder::query()
            ->where('shop_id', $shop->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as day, SUM(amount) as total_amount')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $returnsQtyByDay = ReturnOrderDetail::query()
            ->join('return_orders', 'return_orders.id', '=', 'return_order_details.return_order_id')
            ->where('return_orders.shop_id', $shop->id)
            ->whereBetween('return_orders.created_at', [$startDate, $endDate])
            ->selectRaw('DATE(return_orders.created_at) as day, SUM(return_order_details.quantity) as total_quantity')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $dailyMap = $this->mergeDailyData($salesByDay, $salesQtyByDay, $returnsByDay, $returnsQtyByDay);
        $points = $this->groupByPeriod($dailyMap, $grouping, $startDate, $endDate);

        return $this->json('Analytics summary', [
            'points' => $points,
        ]);
    }

    public function topProducts(Request $request)
    {
        $shop = generaleSetting('shop');
        $limit = (int) ($request->limit ?? 10);
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $aggregates = OrderProduct::query()
            ->join('orders', 'orders.id', '=', 'order_products.order_id')
            ->where('orders.shop_id', $shop->id)
            ->where('orders.order_status', OrderStatus::DELIVERED->value)
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->selectRaw('order_products.product_id as product_id, SUM(order_products.quantity) as total_sales_count, SUM(order_products.quantity * order_products.price) as total_sales_amount')
            ->groupBy('order_products.product_id')
            ->orderByDesc('total_sales_count')
            ->limit($limit)
            ->get();

        $products = Product::with('media')
            ->whereIn('id', $aggregates->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $result = $aggregates->map(function ($row) use ($products) {
            $product = $products->get($row->product_id);
            $price = $product?->discount_price ?? $product?->price ?? 0;
            $mrp = $product?->price ?? 0;

            return [
                'id' => $row->product_id,
                'name' => $product?->name ?? '',
                'image_url' => $product?->thumbnail ?? asset('default/default.jpg'),
                'price' => (float) $price,
                'mrp' => (float) $mrp,
                'total_sales_count' => (int) $row->total_sales_count,
                'total_sales_amount' => (float) $row->total_sales_amount,
            ];
        })->values();

        return $this->json('Top products', [
            'products' => $result,
        ]);
    }

    public function topRatedProducts(Request $request)
    {
        $shop = generaleSetting('shop');
        $limit = (int) ($request->limit ?? 10);

        $products = Product::with('media')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->where('shop_id', $shop->id)
            ->having('reviews_count', '>', 0)
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->limit($limit)
            ->get();

        $result = $products->map(function ($product) {
            $price = $product->discount_price ?? $product->price ?? 0;
            $mrp = $product->price ?? 0;

            return [
                'id' => $product->id,
                'name' => $product->name ?? '',
                'image_url' => $product->thumbnail ?? asset('default/default.jpg'),
                'price' => (float) $price,
                'mrp' => (float) $mrp,
                'average_rating' => round((float) ($product->reviews_avg_rating ?? 0), 1),
                'total_reviews' => (int) ($product->reviews_count ?? 0),
            ];
        })->values();

        return $this->json('Top rated products', [
            'products' => $result,
        ]);
    }

    public function topCustomers(Request $request)
    {
        $shop = generaleSetting('shop');
        $limit = (int) ($request->limit ?? 10);
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $orderAgg = Order::query()
            ->where('shop_id', $shop->id)
            ->where('order_status', OrderStatus::DELIVERED->value)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('customer_id, COUNT(*) as total_orders, SUM(total_amount) as total_amount')
            ->groupBy('customer_id')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get();

        $returnAgg = ReturnOrder::query()
            ->where('shop_id', $shop->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('customer_id, COUNT(*) as total_returns')
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $customers = Customer::with('user.media')
            ->whereIn('id', $orderAgg->pluck('customer_id'))
            ->get()
            ->keyBy('id');

        $result = $orderAgg->map(function ($row) use ($customers, $returnAgg) {
            $customer = $customers->get($row->customer_id);
            $user = $customer?->user;
            $returns = $returnAgg->get($row->customer_id);

            return [
                'name' => $user?->full_name ?? $user?->name ?? '',
                'total_orders' => (int) $row->total_orders,
                'total_returns' => (int) ($returns->total_returns ?? 0),
                'total_amount' => (float) $row->total_amount,
                'image_url' => $user?->thumbnail ?? asset('default/profile.jpg'),
                'mobile_number' => $user?->phone ?? '',
            ];
        })->values();

        return $this->json('Top customers', [
            'customers' => $result,
        ]);
    }

    private function resolveDateRange(Request $request): array
    {
        $filter = $request->get('filter', 'monthly');
        $now = Carbon::now();

        $startDate = $now->copy()->startOfMonth();
        $endDate = $now->copy()->endOfDay();
        $grouping = 'month';

        if ($filter === 'today') {
            $startDate = $now->copy()->startOfDay();
            $endDate = $now->copy()->endOfDay();
            $grouping = 'today';
        } elseif ($filter === 'weekly') {
            $startDate = $now->copy()->startOfWeek(Carbon::MONDAY);
            $endDate = $now->copy()->endOfWeek(Carbon::SUNDAY);
            $grouping = 'weekday';
        } elseif ($filter === 'monthly') {
            $startDate = $now->copy()->subMonths(5)->startOfMonth();
            $endDate = $now->copy()->endOfDay();
            $grouping = 'month';
        } elseif ($filter === 'yearly') {
            $startDate = $now->copy()->startOfYear();
            $endDate = $now->copy()->endOfYear();
            $grouping = 'month';
        } elseif ($filter === 'custom') {
            $start = $request->get('start_date');
            $end = $request->get('end_date');
            $startDate = $start ? Carbon::parse($start)->startOfDay() : $now->copy()->subDays(6)->startOfDay();
            $endDate = $end ? Carbon::parse($end)->endOfDay() : $now->copy()->endOfDay();
            $grouping = $startDate->diffInDays($endDate) <= 31 ? 'day' : 'month_year';
        }

        return [$startDate, $endDate, $grouping];
    }

    private function mergeDailyData(
        Collection $salesByDay,
        Collection $salesQtyByDay,
        Collection $returnsByDay,
        Collection $returnsQtyByDay
    ): Collection {
        $days = $salesByDay
            ->keys()
            ->merge($salesQtyByDay->keys())
            ->merge($returnsByDay->keys())
            ->merge($returnsQtyByDay->keys())
            ->unique();

        return $days->mapWithKeys(function ($day) use ($salesByDay, $salesQtyByDay, $returnsByDay, $returnsQtyByDay) {
            return [
                $day => [
                    'sales_amount' => (float) ($salesByDay[$day]->total_amount ?? 0),
                    'returns_amount' => (float) ($returnsByDay[$day]->total_amount ?? 0),
                    'sales_count' => (int) ($salesQtyByDay[$day]->total_quantity ?? 0),
                    'returns_count' => (int) ($returnsQtyByDay[$day]->total_quantity ?? 0),
                ],
            ];
        });
    }

    private function groupByPeriod(Collection $dailyMap, string $grouping, Carbon $startDate, Carbon $endDate): array
    {
        if ($grouping === 'today') {
            $totals = $dailyMap->reduce(function ($carry, $item) {
                $carry['sales_amount'] += $item['sales_amount'];
                $carry['returns_amount'] += $item['returns_amount'];
                $carry['sales_count'] += $item['sales_count'];
                $carry['returns_count'] += $item['returns_count'];
                return $carry;
            }, ['sales_amount' => 0, 'returns_amount' => 0, 'sales_count' => 0, 'returns_count' => 0]);

            return [[
                'period' => 'Today',
                'sales_amount' => (float) $totals['sales_amount'],
                'returns_amount' => (float) $totals['returns_amount'],
                'sales_count' => (int) $totals['sales_count'],
                'returns_count' => (int) $totals['returns_count'],
            ]];
        }

        $groups = [];

        foreach ($dailyMap as $day => $values) {
            $date = Carbon::parse($day);

            if ($grouping === 'weekday') {
                $label = $date->format('D');
            } elseif ($grouping === 'day') {
                $label = $date->format('d/m');
            } elseif ($grouping === 'month_year') {
                $label = $date->format('M Y');
            } else {
                $label = $date->format('M');
            }

            if (! isset($groups[$label])) {
                $groups[$label] = [
                    'period' => $label,
                    'sales_amount' => 0,
                    'returns_amount' => 0,
                    'sales_count' => 0,
                    'returns_count' => 0,
                ];
            }

            $groups[$label]['sales_amount'] += $values['sales_amount'];
            $groups[$label]['returns_amount'] += $values['returns_amount'];
            $groups[$label]['sales_count'] += $values['sales_count'];
            $groups[$label]['returns_count'] += $values['returns_count'];
        }

        return array_values($groups);
    }
}

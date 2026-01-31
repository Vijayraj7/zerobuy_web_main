<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Shop;
use App\Models\Review;
use App\Models\ShopReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', '30'); // Default 30 days
        $startDate = Carbon::now()->subDays($period);

        // Overview Statistics
        $stats = [
            'total_revenue' => Order::where('created_at', '>=', $startDate)
                ->where('payment_status', 'paid')
                ->sum('total_amount'),
            'total_orders' => Order::where('created_at', '>=', $startDate)->count(),
            'total_customers' => Customer::where('created_at', '>=', $startDate)->count(),
            'total_products' => Product::where('created_at', '>=', $startDate)->count(),
            'total_shops' => Shop::where('created_at', '>=', $startDate)->count(),
            'pending_orders' => Order::where('order_status', 'pending')->count(),
            'completed_orders' => Order::where('order_status', 'delivered')
                ->where('created_at', '>=', $startDate)
                ->count(),
            'cancelled_orders' => Order::where('order_status', 'cancelled')
                ->where('created_at', '>=', $startDate)
                ->count(),
        ];

        // Revenue Trend (Last 30 days)
        $revenueTrend = Order::selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
            ->where('created_at', '>=', $startDate)
            ->where('payment_status', 'paid')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Orders Trend
        $ordersTrend = Order::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Order Status Distribution
        $ordersByStatus = Order::selectRaw('order_status, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('order_status')
            ->get();

        // Top Products
        $topProducts = Product::withCount(['orderItems' => function ($query) use ($startDate) {
            $query->whereHas('order', function ($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate);
            });
        }])
            ->orderBy('order_items_count', 'desc')
            ->limit(10)
            ->get();

        // Top Shops by Revenue
        $topShops = Shop::withSum(['orders as revenue' => function ($query) use ($startDate) {
            $query->where('created_at', '>=', $startDate)
                ->where('payment_status', 'paid');
        }], 'total_amount')
            ->orderBy('revenue', 'desc')
            ->limit(10)
            ->get();

        // Customer Growth
        $customerGrowth = Customer::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Product Categories Performance
        $categoryPerformance = DB::table('categories')
            ->join('product_categories', 'categories.id', '=', 'product_categories.category_id')
            ->join('products', 'product_categories.product_id', '=', 'products.id')
            ->leftJoin('order_products', 'products.id', '=', 'order_products.product_id')
            ->leftJoin('orders', function ($join) use ($startDate) {
                $join->on('order_products.order_id', '=', 'orders.id')
                    ->where('orders.created_at', '>=', $startDate);
            })
            ->select('categories.name', DB::raw('COUNT(DISTINCT order_products.id) as sales_count'))
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('sales_count', 'desc')
            ->limit(8)
            ->get();

        // Payment Methods Distribution
        $paymentMethods = Order::selectRaw('payment_method, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('payment_method')
            ->get();

        // Average Order Value
        $avgOrderValue = Order::where('created_at', '>=', $startDate)
            ->where('payment_status', 'paid')
            ->avg('total_amount');

        // Shop Performance Metrics
        $shopMetrics = [
            'active_shops' => Shop::isActive()->count(),
            'total_reports' => ShopReport::where('created_at', '>=', $startDate)->count(),
            'verified_shops' => Shop::where('is_verified', true)->count(),
            'branded_shops' => Shop::where('is_branded', true)->count(),
        ];

        // Reviews Statistics
        $reviewStats = [
            'total_reviews' => Review::where('created_at', '>=', $startDate)->count(),
            'average_rating' => Review::where('created_at', '>=', $startDate)->avg('rating'),
        ];

        // Hourly Sales Pattern (for current week)
        $hourlySales = Order::selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->startOfWeek())
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Monthly Comparison (Current vs Previous)
        $currentMonthRevenue = Order::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $previousMonthRevenue = Order::whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $revenueGrowth = $previousMonthRevenue > 0
            ? (($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100
            : 0;

        return view('admin.analytics.index', compact(
            'stats',
            'revenueTrend',
            'ordersTrend',
            'ordersByStatus',
            'topProducts',
            'topShops',
            'customerGrowth',
            'categoryPerformance',
            'paymentMethods',
            'avgOrderValue',
            'shopMetrics',
            'reviewStats',
            'hourlySales',
            'revenueGrowth',
            'period'
        ));
    }

    public function export(Request $request)
    {
        // Export analytics data as CSV/Excel
        // Implementation for data export
    }
}

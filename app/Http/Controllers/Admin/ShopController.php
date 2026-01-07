<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShopCreateRequest;
use App\Http\Requests\ShopPasswordResetRequest;
use App\Models\Notification;
use App\Models\Review;
use App\Models\Shop;
use App\Models\State;
use App\Models\Page;
use App\Repositories\ShopRepository;
use Illuminate\Support\Facades\Hash;
use App\Models\BusinessCategory;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\ReturnOrder;
use App\Enums\OrderStatus;
use App\Models\DeliverySetting;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use DataTables;

class ShopController extends Controller
{
    /**
     * Display a listing of the shops.
     */
    public function index(Request $request)
    {
        $shops = Shop::paginate(20);

        if ($request->ajax()) {
            // $query = Shop::with(['user', 'products', 'orders']);
            $query = Shop::with('user')->withCount(['products', 'orders']);

            if ($request->startDate) {
                $query->whereDate('shops.created_at', '>=', $request->startDate);
            }
            if ($request->endDate) {
                $query->whereDate('shops.created_at', '<=', $request->endDate);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn(
                    'created_at',
                    fn($shop) =>
                    $shop->created_at?->format('d-m-Y | h:i A')
                )
                ->addColumn('logo', function ($shop) {
                    return '<div class="payment-image"><img class="img-fit" src="' . $shop->logo . '"></div>';
                })

                ->addColumn(
                    'shop_id_display',
                    fn($shop) =>
                    'STR0' . $shop->id
                )
                ->filterColumn('shop_id_display', function ($query, $keyword) {
                    $keyword = str_replace('STR0', '', $keyword);
                    $query->where('shops.id', 'LIKE', "%$keyword%");
                })
                ->orderColumn(
                    'shop_id_display',
                    fn($query, $keyword) =>
                    $query->orderBy('shops.id', $keyword)
                )

                ->addColumn('phone', function ($shop) {
                    return $shop->user?->phone ?? '-';
                })
                ->orderColumn('phone', function ($query, $keyword) {
                    $query->join('users', 'shops.user_id', '=', 'users.id')
                        ->orderBy('users.phone', $keyword)
                        ->select('shops.*');
                })
                ->filterColumn('phone', function ($query, $keyword) {
                    $query->whereHas('user', function ($q) use ($keyword) {
                        $q->where('phone', 'LIKE', "%{$keyword}%");
                    });
                })
                ->addColumn('branded', function ($shop) {
                    return '
                    <label class="switch mb-0">
                        <a href="' . route('admin.shop.branded.toggle', $shop->id) . '">
                            <input type="checkbox" ' . ($shop->is_branded ? 'checked' : '') . '>
                            <span class="slider round"></span>
                        </a>
                    </label>';
                })
                ->addColumn('verified', function ($shop) {
                    return '
                    <label class="switch mb-0">
                        <a href="' . route('admin.shop.verify.toggle', $shop->id) . '">
                            <input type="checkbox" ' . ($shop->is_verified ? 'checked' : '') . '>
                            <span class="slider round"></span>
                        </a>
                    </label>';
                })
                ->addColumn('status', function ($shop) {
                    return '
                    <label class="switch mb-0">
                        <a href="' . route('admin.shop.status.toggle', $shop->id) . '">
                            <input type="checkbox" ' . ($shop->user?->is_active ? 'checked' : '') . '>
                            <span class="slider round"></span>
                        </a>
                    </label>';
                })
                ->addColumn('products', function ($shop) {
                    return '<a href="' . route('admin.shop.products', $shop->id) . '" class="badge badge-square badge-primary" data-bs-toggle="tooltip" title="Click here to view total products">
                        ' . $shop->products_count . '
                    </a>';
                })

                ->addColumn('orders', function ($shop) {
                    return '<a href="' . route('admin.shop.orders', $shop->id) . '" class="badge badge-square badge-info" data-bs-toggle="tooltip" title="Click here to view total orders">
                        ' . $shop->orders_count . '
                    </a>';
                })

                ->orderColumn('products', 'products_count $1')
                ->orderColumn('orders', 'orders_count $1')

                ->addColumn('action', function ($shop) {
                    $btn = '';
                    // if (auth()->user()->can('admin.shop.show')) {
                    $btn .= '
                    <a class="circleIcon" href="' . route('admin.shop.show', $shop->id) . '">
                        <img src="' . asset('assets/icons-admin/eye.svg') . '">
                    </a>';
                    // }
                    // if (auth()->user()->can('admin.shop.edit')) {
                    $btn .= '
                    <a class="circleIcon" href="' . route('admin.shop.edit', $shop->id) . '">
                        <img src="' . asset('assets/icons-admin/edit.svg') . '">
                    </a>';
                    // }
                    return $btn;
                })

                ->rawColumns(['logo', 'phone', 'branded', 'verified', 'status', 'products', 'orders', 'shop_id_display', 'action'])
                ->make(true);
        }

        return view('admin.shop.index', compact('shops'));
    }

    /**
     * Create a new shop.
     */
    public function create()
    {
        // return view('admin.shop.create');
        $states = State::orderBy('name')->get();
        $sellerTerms = Page::where('slug', 'seller-terms-of-service')
            ->where('is_active', 1)
            ->first();
        $businessCategories = BusinessCategory::where('status', 1)->get();
        return view('admin.shop.create-edit', compact('states', 'businessCategories', 'sellerTerms'));
    }

    /**
     * Store a newly created shop.
     */
    public function store(ShopCreateRequest $request)
    {
        if ($request->terms_condition_status != 1) {
            return response()->json([
                'status' => 'terms_required'
            ]);
        }

        ShopRepository::storeByRequest($request);

        return response()->json([
            'status'   => 'success',
            'message'  => 'Shop created successfully',
            'redirect' => route('admin.shop.index')
        ]);
    }


    /**
     * Display the specified shop.
     */

    // public function show(Shop $shop)
    // {
    //     Notification::where('url', '/admin/shops/' . $shop->id)->whereNull('shop_id')->where('is_read', false)->update(['is_read' => true]);

    //     // Orders count by status for this shop
    //     $orderCounts = Order::where('shop_id', $shop->id)
    //         ->select('order_status', DB::raw('COUNT(*) as total'))
    //         ->groupBy('order_status')
    //         ->pluck('total', 'order_status');

    //     // Returned orders (from return_orders table)
    //     $returnedCount = ReturnOrder::where('shop_id', $shop->id)->count();

    //     // Order overview
    //     $orderOverview = [
    //         'pending'   => $orderCounts[OrderStatus::PENDING->value] ?? 0,
    //         'shipped'   => $orderCounts[OrderStatus::SHIPPED->value] ?? 0,
    //         'delivered' => $orderCounts[OrderStatus::DELIVERED->value] ?? 0,
    //         'cancelled' => $orderCounts[OrderStatus::CANCELLED->value] ?? 0,
    //         'returned'  => $returnedCount,
    //     ];
    //     return view('admin.shop.show', compact('shop', 'orderOverview'));
    // }

    public function show(Shop $shop)
    {
        // Mark notification read
        Notification::where('url', '/admin/shops/' . $shop->id)
            ->whereNull('shop_id')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        /* ---------------- TOTAL SALES ---------------- */
        $totalSales = $shop->orders()
            ->where('order_status', OrderStatus::DELIVERED->value)
            ->sum('payable_amount');

        // $totalSales = $shop->orders()   //consider shipped + delivered as sales
        //     ->whereIn('order_status', [
        //         OrderStatus::SHIPPED->value,
        //         OrderStatus::DELIVERED->value
        //     ])->sum('payable_amount');

        // $totalSales = $shop->orders()   //TODAY / MONTH / YEAR SALES
        //     ->where('order_status', OrderStatus::DELIVERED->value)
        //     ->whereMonth('created_at', now()->month)
        //     ->whereYear('created_at', now()->year)
        //     ->sum('payable_amount');

        /* ---------------- ORDER OVERVIEW ---------------- */
        $orderCounts = $shop->orders()
            ->select('order_status', DB::raw('COUNT(*) as total'))
            ->groupBy('order_status')
            ->pluck('total', 'order_status');

        $orderOverview = [
            'pending'   => $orderCounts[OrderStatus::PENDING->value] ?? 0,
            'shipped'   => $orderCounts[OrderStatus::SHIPPED->value] ?? 0,
            'delivered' => $orderCounts[OrderStatus::DELIVERED->value] ?? 0,
            'cancelled' => $orderCounts[OrderStatus::CANCELLED->value] ?? 0,
            'returned'  => $shop->returnOrders()->count(),
        ];

        /* ---------------- SUBSCRIPTION ---------------- */
        // $subscription = $shop->currentSubscription;
        $subscription = $shop->currentSubscription()->with('plan')->first();


        $daysLeft = 0;
        $totalDays = 0;

        // if ($subscription && $subscription->ends_at) {
        //     $daysLeft  = now()->diffInDays(Carbon::parse($subscription->ends_at), false);
        //     $totalDays = Carbon::parse($subscription->starts_at)
        //                     ->diffInDays(Carbon::parse($subscription->ends_at));
        // }
        if ($subscription && $subscription->ends_at && $subscription->starts_at) {
            $daysLeft  = now()->diffInDays($subscription->ends_at, false);
            $totalDays = $subscription->starts_at->diffInDays($subscription->ends_at);
        }

        /* ---------------- SALES & ORDER CHART (YEAR) ---------------- */
        $salesData = $shop->orders()
            ->whereYear('created_at', now()->year)
            ->selectRaw('MONTH(created_at) month, SUM(payable_amount) total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $orderData = $shop->orders()
            ->whereYear('created_at', now()->year)
            ->selectRaw('MONTH(created_at) month, COUNT(*) total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $returnData = $shop->returnOrders()
            ->whereYear('created_at', now()->year)
            ->selectRaw('MONTH(created_at) month, COUNT(*) total')
            ->groupBy('month')
            ->pluck('total', 'month');

        // Fill missing months
        $months = collect(range(1, 12));

        $chartData = [
            'sales'  => $months->map(fn($m) => (int) ($salesData[$m] ?? 0)),
            'orders' => $months->map(fn($m) => (int) ($orderData[$m] ?? 0)),
            'returns' => $months->map(fn($m) => (int) ($returnData[$m] ?? 0)),
        ];

        return view('admin.shop.show', compact(
            'totalSales',
            'shop',
            'orderOverview',
            'subscription',
            'daysLeft',
            'totalDays',
            'chartData'
        ));
    }

    /**
     * Edit the shop.
     */
    public function edit(Shop $shop)
    {
        // return view('admin.shop.edit', compact('shop'));
        $shop->load('deliverySetting');
        $states = State::orderBy('name')->get();
        $businessCategories = BusinessCategory::where('status', 1)->get();
        $setting = DeliverySetting::with([
            'amountRules',
            'stateCharges.state',
        ])->where('shop_id', $shop->id)->first();
        // dd(json_encode($setting->stateCharges));

        return view('admin.shop.create-edit', compact('shop', 'states', 'businessCategories', 'setting'));
    }

    /**
     * Update the shop.
     */
    public function update(Request $request, Shop $shop)
    {
        if (app()->environment() == 'local' && $shop->user->email == 'shop@readyecommerce.com') {
            return back()->with('demoMode', 'You can not update the shop in demo mode');
        }
        // dd($request->all());
        // store shop from shopRepository
        ShopRepository::updateByRequest($shop, $request);

        return response()->json([
            'status'   => 'success',
            'message'  => 'Shop updated successfully',
            'redirect' => route('admin.shop.index')
        ]);
    }

    /**
     * Toggle the status of the shop user.
     */
    public function statusToggle(Shop $shop)
    {
        if (app()->environment() == 'local' && $shop->user->email == 'shop@readyecommerce.com') {
            return back()->with('demoMode', 'You can not update status of the shop in demo mode');
        }

        $user = $shop->user;
        if ($user->hasRole('root')) {
            return back()->with('error', __('You can not update status of the root shop'));
        }

        // Update the user status
        $shop->user()->update([
            'is_active' => ! $shop->user->is_active,
        ]);

        return back()->withSuccess(__('Status updated successfully'));
    }

    /**
     * Toggle the status of the shop user.
     */
    public function brandedToggle(Shop $shop)
    {
        if (app()->environment() == 'local' && $shop->user->email == 'shop@readyecommerce.com') {
            return back()->with('demoMode', 'You can not update status of the shop in demo mode');
        }

        // $user = $shop->user;
        // if ($user->hasRole('root')) {
        //     return back()->with('error', __('You can not update status of the root shop'));
        // }

        // Update the user status
        $shop->update([
            'is_branded' => ! $shop->is_branded,
        ]);

        return back()->withSuccess(__('Branded updated successfully'));
    }

    /**
     * Toggle the status of the shop user.
     */
    public function verifyToggle(Shop $shop)
    {
        if (app()->environment() == 'local' && $shop->user->email == 'shop@readyecommerce.com') {
            return back()->with('demoMode', 'You can not update status of the shop in demo mode');
        }

        // $user = $shop->user;
        // if ($user->hasRole('root')) {
        //     return back()->with('error', __('You can not update status of the root shop'));
        // }

        // Update the user status
        $shop->update([
            'is_verified' => ! $shop->is_verified,
        ]);

        return back()->withSuccess(__('Verified updated successfully'));
    }

    /**
     * Display the shop orders.
     */
    public function orders(Shop $shop)
    {
        $orders = $shop->orders()->paginate(20);

        return view('admin.shop.orders', compact('shop', 'orders'));
    }

    /**
     * Display the shop products.
     */
    public function products(Shop $shop)
    {
        $products = $shop->products()->paginate(20);

        return view('admin.shop.products', compact('shop', 'products'));
    }

    /**
     * Display the shop category.
     */
    public function categories(Shop $shop)
    {
        $categories = $shop->categories()->paginate(20);

        return view('admin.shop.category', compact('shop', 'categories'));
    }

    /**
     * Display the shop reviews.
     */
    public function reviews(Shop $shop)
    {
        $reviews = $shop->reviews()->withoutGlobalScopes()->latest('id')->paginate(20);

        return view('admin.shop.reviews', compact('shop', 'reviews'));
    }

    public function resetPassword(Shop $shop, ShopPasswordResetRequest $request)
    {
        if (app()->environment() == 'local' && $shop->user->email == 'shop@readyecommerce.com') {
            return back()->with('demoMode', 'You can not update status of the shop in demo mode');
        }

        // Update the user status
        $shop->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->withSuccess(__('Shop password reset successfully'));
    }

    public function toggleReview($reviewId)
    {
        $review = Review::withoutGlobalScopes()->find($reviewId);

        $review->update([
            'is_active' => ! $review->is_active,
        ]);

        $message = $review->is_active ? __('Review activated successfully') : __('Review deactivated successfully');

        return back()->withSuccess($message);
    }
}

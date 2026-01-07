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
    public function orders(Request $request, Shop $shop)
    {
        // dd($shopId);
        $query = Order::query()
            ->select('orders.*') 
            ->with(['shop', 'address', 'orderProducts'])
            ->where('orders.shop_id', $shop->id);
        if ($request->status) {
            $query->where('orders.order_status', $request->status);
        }
        if ($request->startDate) {
            $query->whereDate('orders.created_at', '>=', $request->startDate);
        }
        if ($request->endDate) {
            $query->whereDate('orders.created_at', '<=', $request->endDate);
        }

        if ($request->ajax()) {
            return datatables()->eloquent($query)
            ->addIndexColumn()
            ->editColumn('created_at', fn($row) =>
                $row->created_at?->format('d-m-Y | h:i A')
            )
            ->addColumn('order_id_display', fn($row) =>
                'ORD0' . $row->id
            )  
            ->addColumn('customer_name', fn($row) =>
                $row->address?->name ?? '-'
            )
            ->addColumn('customer_phone', fn($row) =>
                $row->address?->phone ?? '-'
            )
            ->addColumn('total_quantity', fn($row) =>
                $row->orderProducts->sum('quantity')
            )
            ->editColumn('payable_amount', fn($row) =>
                number_format($row->payable_amount, 2)
            )

            // ✔ SEARCH — ORDER ID
            ->filterColumn('order_id_display', function ($query, $keyword) {
                $keyword = str_replace('ORD0', '', $keyword);
                $query->where('orders.id', 'LIKE', "%$keyword%");
            }) 

            // ✔ SORT BY RELEVANT FIELDS
            ->orderColumn('order_id_display', fn($query, $order) =>
                $query->orderBy('orders.id', $order)
            ) 
            ->orderColumn('customer_name', function ($query, $order) {
                $query->join('addresses', 'orders.address_id', '=', 'addresses.id')
                    ->orderBy('addresses.name', $order)
                    ->select('orders.*');
            })
            ->orderColumn('customer_phone', function ($query, $order) {
                $query->join('addresses', 'orders.address_id', '=', 'addresses.id')
                    ->orderBy('addresses.phone', $order)
                    ->select('orders.*');
            })
            ->orderColumn('total_quantity', function ($query, $order) {
                $query->withSum('orderProducts as qty_sum', 'quantity')
                    ->orderBy('qty_sum', $order);
            })
            ->addColumn('order_status_badge', function ($row) {
                return match (strtolower($row->order_status->value)) {
                    'pending'     => '<span class="badge bg-warning">Pending</span>',
                    'confirm'     => '<span class="badge bg-info">Confirm</span>',
                    'shipped'     => '<span class="badge bg-primary">Shipped</span>',
                    'delivered'   => '<span class="badge bg-success">Delivered</span>',
                    'cancelled'   => '<span class="badge bg-danger">Cancelled</span>',
                    default       => '<span class="badge bg-secondary">'.$row->order_status->value.'</span>',
                };
            })

            ->addColumn('actions', function ($row) {
                $downloadUrl = route('shop.download-invoice', $row->id);
                return '<a href="'.route('shop.order.show',$row->id).'"
                        class="btn btn-primary btn-sm"><i class="fa fa-eye"></i></a>

                        <a href="'.$downloadUrl.'" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" data-bs-title="Download Invoice">  <i class="fa fa-download"></i> </a>
                        
                        ';
            })

            ->rawColumns(['order_status_badge', 'actions'])
            ->toJson();
        }

        return view('admin.shop.orders', [
            'shop'   => $shop,
            'shopId' => $shop->id,
        ]);
    }
    // public function orders(Shop $shop)
    // {
    //     $orders = $shop->orders()->paginate(20);

    //     return view('admin.shop.orders', compact('shop', 'orders'));
    // }

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

    public function returnOrders(Request $request, Shop $shop)
    {
        $query = ReturnOrder::query()
            ->with([
                'order:id,order_code,created_at',
                'customer.user:id,name,phone',
                'returnProducts'
            ])
            ->where('shop_id', $shop->id);

        // FILTERS
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->startDate) {
            $query->whereDate('created_at', '>=', $request->startDate);
        }

        if ($request->endDate) {
            $query->whereDate('created_at', '<=', $request->endDate);
        }

        if ($request->ajax()) {
            return datatables()->eloquent($query)
                ->addIndexColumn()

                // RETURN DATE
                ->editColumn('created_at', fn($row) =>
                    $row->created_at->format('d-m-Y')
                )

                // RETURN ID
                ->addColumn('return_id', fn($row) =>
                    'RTN0' . $row->id
                )
                ->filterColumn('return_id', function ($query, $keyword) {
                    $keyword = str_replace('RTN0', '', $keyword);
                    $query->where('id', 'LIKE', "%$keyword%");
                }) 

                // ORDER DATE
                ->addColumn('order_date', fn($row) =>
                    optional($row->order)->created_at?->format('d-m-Y') ?? '-'
                )

                // ORDER NAME / CODE
                ->addColumn('order_id', fn($row) =>
                    'ORD0' . $row->order_id
                )
                ->filterColumn('order_id', function ($query, $keyword) {
                    $keyword = str_replace('ORD0', '', $keyword);
                    $query->where('order_id', 'LIKE', "%$keyword%");
                })

                // CUSTOMER NAME
                ->addColumn('customer_name', fn($row) =>
                    $row->customer?->user?->name ?? '-'
                )

                ->filterColumn('customer_name', function ($query, $keyword) {
                    $query->whereHas('customer.user', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })

                ->orderColumn('customer_name', function ($query, $order) {
                    $query->join('customers', 'customers.id', '=', 'return_orders.customer_id')
                        ->join('users', 'users.id', '=', 'customers.user_id')
                        ->orderBy('users.name', $order)
                        ->select('return_orders.*');
                })

                // MOBILE NUMBER
                ->addColumn('customer_phone', fn($row) =>
                    $row->customer?->user?->phone ?? '-'
                )

                // QUANTITY (SUM)
                ->addColumn('quantity', fn($row) =>
                    $row->returnProducts->sum('quantity')
                )

                // AMOUNT (DETAILS TOTAL)
                ->addColumn('amount', fn($row) =>
                    number_format(
                        $row->returnProducts->sum(fn($p) => $p->price * $p->quantity),
                        2
                    )
                )

                // TOTAL AMOUNT
                ->editColumn('amount', fn($row) =>
                    number_format($row->amount, 2)
                )

                // STATUS BADGE
                ->addColumn('status_badge', function ($row) {
                    return match ($row->status) {
                        'pending'   => '<span class="badge bg-warning">Pending</span>',
                        'approved'  => '<span class="badge bg-success">Approved</span>',
                        'rejected'  => '<span class="badge bg-danger">Rejected</span>',
                        default     => '<span class="badge bg-secondary">'.ucfirst($row->status).'</span>',
                    };
                })

                // ACTIONS
                ->addColumn('actions', function ($row) {
                    $downloadUrl = route('shop.download-invoice', $row->id);
                    return '<a href="'.route('shop.order.show',$row->id).'"
                            class="btn btn-primary btn-sm"><i class="fa fa-eye"></i></a>

                            <a href="'.$downloadUrl.'" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" data-bs-title="Download Invoice">  <i class="fa fa-download"></i> </a>
                            
                            ';
                })
            
                ->rawColumns(['status_badge', 'actions'])
                ->toJson();
        }

        return view('admin.shop.return-orders', compact('shop'));
    }


    public function address(Shop $shop)
    {
        $products = $shop->products()->paginate(20);

        return view('admin.shop.address', compact('shop', 'products'));
    }

    public function followers(Shop $shop)
    {
        $products = $shop->products()->paginate(20);

        return view('admin.shop.followers', compact('shop', 'products'));
    }
}

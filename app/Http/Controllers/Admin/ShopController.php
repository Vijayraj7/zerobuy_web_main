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
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\ReturnOrder;
use App\Enums\OrderStatus;
use App\Models\DeliverySetting;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopFollower;
use Carbon\Carbon;
use DataTables;

class ShopController extends Controller
{ 
    public function index(Request $request)
    {
        $shops = Shop::paginate(20);
        $query = Shop::with('user','currentSubscription.plan')->withCount(['products', 'orders']);
        if ($request->filter) {
            switch ($request->filter) {

                case 'branded':
                    $query->where('is_branded', 1);
                    break;

                case 'verified':
                    $query->where('is_verified', 1);
                    break;

                case 'active':
                    $query->whereHas('user', fn ($q) => $q->where('is_active', 1));
                    break;

                case 'inactive':
                    $query->whereHas('user', fn ($q) => $q->where('is_active', 0));
                    break;
            }
        }
        if ($request->ajax()) { 
            if ($request->startDate) {
                $query->whereDate('shops.created_at', '>=', $request->startDate);
            }
            if ($request->endDate) {
                $query->whereDate('shops.created_at', '<=', $request->endDate);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn( 'created_at', fn($shop) => $shop->created_at?->format('d-m-Y | h:i A') )
                ->addColumn('logo', function ($shop) {
                    return '<div class="payment-image"><img class="img-fit" src="' . $shop->logo . '"></div>';
                })
                ->addColumn('shop_id_display', fn($shop) => 'STR0' . $shop->id )
                ->filterColumn('shop_id_display', function ($query, $keyword) {
                    $keyword = str_replace('STR0', '', $keyword);
                    $query->where('shops.id', 'LIKE', "%$keyword%");
                })
                ->orderColumn( 'shop_id_display', fn($query, $keyword) => $query->orderBy('shops.id', $keyword) )
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

                // ->addColumn('subscription_days', function ($shop) {
                //     $subscription = $shop->currentSubscription;
                //     if (!$subscription) {
                //         return '-';
                //     }
                //     $daysLeft = max(0, now()->startOfDay()->diffInDays($subscription->ends_at->startOfDay(), false));
                //     $totalDays = $subscription->starts_at->diffInDays($subscription->ends_at);
                //     return '<span class=""><b>Days Left : </b>'.$daysLeft.' Days</span> <br> 
                //     <span class=""><b>Total Days : </b>'.$totalDays.' Days</span>'; 
                // })
                ->addColumn('subscription_days', function ($shop) {
                    $subscription = $shop->currentSubscription;

                    if (!$subscription || !$subscription->plan) {
                        return '<span class="badge badge-secondary">No Plan</span>';
                    }

                    $daysLeft = max(
                        0,
                        now()->startOfDay()->diffInDays($subscription->ends_at->startOfDay(), false)
                    );

                    $totalDays = $subscription->starts_at->diffInDays($subscription->ends_at);

                    return '
                        <strong>' . e($subscription->plan->name) . '</strong><br>
                        <span class="text-muted">' . $daysLeft . ' / ' . $totalDays . ' Days</span>
                    ';
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
                    <a class="circleIcon mt-1" href="' . route('admin.shop.edit', $shop->id) . '">
                        <img src="' . asset('assets/icons-admin/edit.svg') . '">
                    </a>';
                    // }
                    return $btn;
                })

                ->rawColumns(['logo', 'phone', 'branded', 'verified', 'status', 'products', 'subscription_days', 'orders', 'shop_id_display', 'action'])
                ->make(true);
        }

        return view('admin.shop.index', compact('shops'));
    }
 
    public function create()
    { 
        $states = State::orderBy('name')->get();
        $sellerTerms = Page::where('slug', 'seller-terms-of-service')->where('is_active', 1)->first();
        $businessCategories = BusinessCategory::where('status', 1)->get();
        return view('admin.shop.create-edit', [
            'states' => $states,
            'businessCategories' => $businessCategories,
            'sellerTerms' => $sellerTerms,
            'formAction' => route('admin.shop.store'),
        ]);
    }
    
    public function store(ShopCreateRequest $request)
    {
        if ($request->terms_condition_status != 1) {
            return response()->json(['status' => 'terms_required']);
        }

        ShopRepository::storeByRequest($request);
        return response()->json([
            'status'   => 'success',
            'message'  => 'Shop created successfully',
            'redirect' => route('admin.shop.index')
        ]);
    } 

    public function show(Shop $shop)
    {
        // Mark notification read
        Notification::where('url', '/admin/shops/' . $shop->id)->whereNull('shop_id')->where('is_read', false)->update(['is_read' => true]); 
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
        $orderCounts = $shop->orders()->select('order_status', DB::raw('COUNT(*) as total'))->groupBy('order_status')->pluck('total', 'order_status');

        $orderOverview = [
            'pending'   => $orderCounts[OrderStatus::PENDING->value] ?? 0,
            'shipped'   => $orderCounts[OrderStatus::SHIPPED->value] ?? 0,
            'delivered' => $orderCounts[OrderStatus::DELIVERED->value] ?? 0,
            'cancelled' => $orderCounts[OrderStatus::CANCELLED->value] ?? 0,
            'returned'  => $shop->returnOrders()->count(),
        ];

        /* ---------------- SUBSCRIPTION ---------------- */
        $subscription = $shop->currentSubscription()->with('plan')->first();

        $daysLeft = 0;
        $totalDays = 0;

        if ($subscription && $subscription->ends_at && $subscription->starts_at) {
            // $daysLeft  = now()->diffInDays($subscription->ends_at, false);
            $daysLeft = max(0, now()->startOfDay()->diffInDays($subscription->ends_at->startOfDay()));
            $totalDays = $subscription->starts_at->diffInDays($subscription->ends_at);
        }

        /* ---------------- SALES & ORDER CHART (TODAY/WEEK/MONTH/YEAR) ---------------- */
        $buildPeriodChartData = function (array $labels, callable $salesResolver, callable $ordersResolver, callable $returnsResolver) {
            return [
                'labels' => $labels,
                'sales' => collect($labels)->map($salesResolver)->values()->all(),
                'orders' => collect($labels)->map($ordersResolver)->values()->all(),
                'returns' => collect($labels)->map($returnsResolver)->values()->all(),
            ];
        };

        $todayDate = Carbon::today();
        $todayHours = collect(range(0, 23));
        $todayLabels = $todayHours->map(fn ($hour) => str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00')->all();

        $weekStart = Carbon::now()->startOfWeek();
        $weekDays = collect(range(0, 6))->map(fn ($day) => $weekStart->copy()->addDays($day));
        $weekLabels = $weekDays->map(fn ($date) => $date->format('D'))->all();

        $monthStart = Carbon::now()->startOfMonth();
        $monthDays = collect(range(1, Carbon::now()->daysInMonth));
        $monthLabels = $monthDays->map(fn ($day) => (string) $day)->all();

        $yearMonths = collect(range(1, 12));
        $yearLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        $chartData = [
            'today' => $buildPeriodChartData(
                $todayLabels,
                fn ($label, $index) => (float) $shop->orders()
                    ->where('order_status', OrderStatus::DELIVERED->value)
                    ->whereDate('created_at', $todayDate)
                    ->whereRaw('HOUR(created_at) = ?', [$todayHours[$index]])
                    ->sum('payable_amount'),
                fn ($label, $index) => (int) $shop->orders()
                    ->whereDate('created_at', $todayDate)
                    ->whereRaw('HOUR(created_at) = ?', [$todayHours[$index]])
                    ->count(),
                fn ($label, $index) => (int) $shop->returnOrders()
                    ->whereDate('created_at', $todayDate)
                    ->whereRaw('HOUR(created_at) = ?', [$todayHours[$index]])
                    ->count(),
            ),
            'week' => $buildPeriodChartData(
                $weekLabels,
                fn ($label, $index) => (float) $shop->orders()
                    ->where('order_status', OrderStatus::DELIVERED->value)
                    ->whereDate('created_at', $weekDays[$index])
                    ->sum('payable_amount'),
                fn ($label, $index) => (int) $shop->orders()
                    ->whereDate('created_at', $weekDays[$index])
                    ->count(),
                fn ($label, $index) => (int) $shop->returnOrders()
                    ->whereDate('created_at', $weekDays[$index])
                    ->count(),
            ),
            'month' => $buildPeriodChartData(
                $monthLabels,
                fn ($label, $index) => (float) $shop->orders()
                    ->where('order_status', OrderStatus::DELIVERED->value)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->whereDay('created_at', $monthDays[$index])
                    ->sum('payable_amount'),
                fn ($label, $index) => (int) $shop->orders()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->whereDay('created_at', $monthDays[$index])
                    ->count(),
                fn ($label, $index) => (int) $shop->returnOrders()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->whereDay('created_at', $monthDays[$index])
                    ->count(),
            ),
            'year' => $buildPeriodChartData(
                $yearLabels,
                fn ($label, $index) => (float) $shop->orders()
                    ->where('order_status', OrderStatus::DELIVERED->value)
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', $yearMonths[$index])
                    ->sum('payable_amount'),
                fn ($label, $index) => (int) $shop->orders()
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', $yearMonths[$index])
                    ->count(),
                fn ($label, $index) => (int) $shop->returnOrders()
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', $yearMonths[$index])
                    ->count(),
            ),
        ];

        $deliverySetting = DeliverySetting::where('shop_id', $shop->id)->first();

        $shop->load('businessCategories');

        return view('admin.shop.show', compact('totalSales', 'shop', 'orderOverview', 'subscription', 'daysLeft', 'totalDays', 'chartData', 'deliverySetting'));
    }  

    public function edit(Shop $shop)
    { 
        $shop->load('deliverySetting');
        $states = State::orderBy('name')->get();
        $businessCategories = BusinessCategory::where('status', 1)->get();
        $setting = DeliverySetting::with(['amountRules', 'stateCharges.state',])->where('shop_id', $shop->id)->first();

        return view('admin.shop.create-edit', [
            'shop' => $shop,
            'states' => $states,
            'businessCategories' => $businessCategories,
            'setting' => $setting,
            'formAction' => route('admin.shop.update', $shop->id),
        ]);
    } 

    public function update(Request $request, Shop $shop)
    {
        if (app()->environment() == 'local' && $shop->user->email == 'shop@readyecommerce.com') {
            return back()->with('demoMode', 'You can not update the shop in demo mode');
        } 
 
        ShopRepository::updateByRequest($shop, $request);

        return response()->json([
            'status'   => 'success',
            'message'  => 'Shop updated successfully.',
            'redirect' => route('admin.shop.index')
        ]);
    }

    public function statusToggle(Shop $shop)
    {
        if (app()->environment() == 'local' && $shop->user->email == 'shop@readyecommerce.com') {
            return back()->with('demoMode', 'You can not update status of the shop in demo mode');
        }

        $user = $shop->user;
        if ($user->hasRole('root')) {
            return back()->with('error', __('You can not update status of the root shop'));
        } 

        $shop->user()->update([
            'is_active' => ! $shop->user->is_active,
        ]);

        return back()->withSuccess(__('Status updated successfully'));
    }

    public function brandedToggle(Shop $shop)
    {
        if (app()->environment() == 'local' && $shop->user->email == 'shop@readyecommerce.com') {
            return back()->with('demoMode', 'You can not update status of the shop in demo mode');
        }  

        $shop->update([
            'is_branded' => ! $shop->is_branded,
        ]);

        return back()->withSuccess(__('Branded updated successfully'));
    }
 
    public function verifyToggle(Shop $shop)
    {
        if (app()->environment() == 'local' && $shop->user->email == 'shop@readyecommerce.com') {
            return back()->with('demoMode', 'You can not update status of the shop in demo mode');
        }
 
        $shop->update([
            'is_verified' => ! $shop->is_verified,
        ]);

        return back()->withSuccess(__('Verified updated successfully'));
    }
 
    public function orders(Request $request, Shop $shop)
    { 
        $query = Order::query()->select('orders.*') ->with(['shop', 'address', 'orderProducts'])->where('orders.shop_id', $shop->id);
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
            ->editColumn('created_at', fn($row) => $row->created_at?->format('d-m-Y | h:i A'))
            ->addColumn('order_id_display', fn($row) => 'ORD0' . $row->id )  
            ->addColumn('customer_name', fn($row) => $row->address?->name ?? '-' )
            ->addColumn('customer_phone', fn($row) => $row->address?->phone ?? '-' )
            ->addColumn('total_quantity', fn($row) => $row->orderProducts->sum('quantity'))
            ->editColumn('payable_amount', fn($row) => number_format($row->payable_amount, 2))

            // SEARCH — ORDER ID
            ->filterColumn('order_id_display', function ($query, $keyword) {
                $keyword = str_replace('ORD0', '', $keyword);
                $query->where('orders.id', 'LIKE', "%$keyword%");
            }) 
            // SORT BY RELEVANT FIELDS
            ->orderColumn('order_id_display', fn($query, $order) =>
                $query->orderBy('orders.id', $order)
            ) 
            ->orderColumn('customer_name', function ($query, $order) {
                $query->join('addresses', 'orders.address_id', '=', 'addresses.id')->orderBy('addresses.name', $order)->select('orders.*');
            })
            ->orderColumn('customer_phone', function ($query, $order) {
                $query->join('addresses', 'orders.address_id', '=', 'addresses.id')->orderBy('addresses.phone', $order)->select('orders.*');
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
                return '<a href="'.route('shop.order.show',$row->id).'" class="btn btn-primary btn-sm"><i class="fa fa-eye"></i></a>

                        <a href="'.$downloadUrl.'" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" data-bs-title="Download Invoice">  <i class="fa fa-download"></i> </a>';
            })

            ->rawColumns(['order_status_badge', 'actions'])
            ->toJson();
        }

        return view('admin.shop.orders', ['shop'   => $shop, 'shopId' => $shop->id,]);
    }  

    public function products(Request $request, Shop $shop)
    {
        $products = $shop->products()->paginate(20);

        if ($request->ajax()) { 
            $query = Product::query()
                ->withCount([
                    'variants',
                    'orderItems as total_sale_count' // 👈 alias
                ])
                ->where('shop_id', $shop->id);

            // $query = Product::query()    //row query
            //     ->where('shop_id', $shop->id)
            //     ->select([
            //         'products.*',
            //         DB::raw('(SELECT COUNT(*) FROM product_variants WHERE product_variants.product_id = products.id) as variants_count'),
            //         DB::raw('(SELECT COUNT(*) FROM order_products WHERE order_products.product_id = products.id) as total_sale_count'),
            //     ]);


            // Status filter (Active / Inactive)
            if ($request->filled('status')) {
                $query->where('is_active', $request->status);
            }

            // Date filters
            if ($request->startDate) {
                $query->whereDate('created_at', '>=', $request->startDate);
            }

            if ($request->endDate) {
                $query->whereDate('created_at', '<=', $request->endDate);
            }

            return datatables()->eloquent($query)
                ->addIndexColumn()

                ->editColumn('created_at', function ($row) {
                    return $row->created_at->format('d-m-Y | h:i A');
                })
                ->addColumn('product_id', fn($row) => 'PRD0' . $row->id)
                ->filterColumn('product_id', function ($query, $keyword) {
                    $keyword = str_replace('PRD0', '', $keyword);
                    $query->where('id', 'LIKE', "%$keyword%");
                })
                ->orderColumn('product_id', function ($query, $order) {
                    $query->orderBy('products.id', $order);
                })   
                ->addColumn('product_image', function ($row) {
                    $img = $row->thumbnail ?? asset('images/no-image.png');
                    return '<img src="'.$img.'" width="50">';
                })  
                ->addColumn('status_badge', function ($row) {
                    return $row->is_active
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-danger">Inactive</span>';
                }) 
                ->addColumn('actions', function ($row) use ($shop) {
                    return '
                        <a href="'.route('shop.product.edit', $row->id).'" class="btn btn-sm btn-primary">
                            <i class="fa fa-edit"></i>
                        </a>
                        <a href="'.route('admin.product.show', $row->id).'" class="btn btn-sm btn-secondary mt-1">
                            <i class="fa fa-eye"></i>
                        </a>
                    ';
                })

                ->rawColumns(['product_image', 'status_badge', 'actions'])
                ->toJson();
        }

        return view('admin.shop.products', compact('shop','products'));
    }  

    public function categories(Request $request, Shop $shop)
    {
        $search    = $request->input('search');
        $sortBy    = $request->input('sort_by', 'business_categories.name');
        $sortOrder = $request->input('sort_order', 'asc');

        $query = BusinessCategory::whereHas('shops', function ($q) use ($shop) {
            $q->where('shop_id', $shop->id);
        });

        // 🔍 SEARCH (BUSINESS CATEGORY OR CATEGORY)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('business_categories.name', 'like', "%{$search}%")
                ->orWhereHas('categories', function ($c) use ($search) {
                    $c->where('name', 'like', "%{$search}%");
                });
            });
        }

        // 🔃 SORT BUSINESS CATEGORY
        $query->orderBy($sortBy, $sortOrder);

        // 📦 LOAD CATEGORIES (DON'T FILTER HERE!)
        $query->with([
            'categories' => function ($q) use ($shop) {
                $q->withCount([
                    'products as products_count' => function ($p) use ($shop) {
                        $p->where('products.shop_id', $shop->id);
                    }
                ]);
            }
        ]);

        $Categories = $query->paginate(10)->withQueryString();

        return view('admin.shop.category', compact('Categories', 'shop'));
    }

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
            ->with(['order:id,order_code,created_at', 'customer.user:id,name,phone', 'returnProduct'])
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
                ->editColumn('created_at', fn($row) => $row->created_at->format('d-m-Y')) 
                ->addColumn('return_code', fn($row) => '<a href="'.route('shop.returnOrder.show', $row->id).'" class="text-primary fw-bold">' . ($row->return_code ? $row->return_code : 'RTN0' . $row->id) . '</a>')
                ->filterColumn('return_code', function ($query, $keyword) {
                    $keyword = str_replace('RTN0', '', $keyword);
                    $query->where(function ($q) use ($keyword) {
                        $q->where('return_code', 'LIKE', "%$keyword%")
                          ->orWhere('id', 'LIKE', "%$keyword%");
                    });
                })  
                ->addColumn('order_date', fn($row) => optional($row->order)->created_at?->format('d-m-Y') ?? '-') 
                ->addColumn('order_code', fn($row) => $row->order?->order_code ?? 'ORD0' . $row->order_id)
                ->filterColumn('order_code', function ($query, $keyword) {
                    $keyword = str_replace('ORD0', '', $keyword);
                    $query->whereHas('order', function ($q) use ($keyword) {
                        $q->where('order_code', 'LIKE', "%$keyword%");
                    })->orWhere('order_id', 'LIKE', "%$keyword%");
                }) 
                ->addColumn('customer_name', fn($row) => $row->customer?->user?->name ?? '-')
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
                ->addColumn('customer_phone', fn($row) => $row->customer?->user?->phone ?? '-') 
                ->addColumn('quantity', fn($row) => $row->returnProduct->sum('quantity')) 
                ->addColumn('amount', fn($row) => number_format($row->returnProduct->sum(fn($p) => $p->price * $p->quantity), 2)) 
                ->editColumn('amount', fn($row) => number_format($row->amount, 2)) 
                ->addColumn('status_badge', function ($row) {
                    return match ($row->status) {
                        'Pending'   => '<span class="badge bg-warning">Pending</span>',
                        'Approved'  => '<span class="badge bg-info">Approved</span>',
                        'Completed'  => '<span class="badge bg-success">Completed</span>',
                        'Rejected'  => '<span class="badge bg-danger">Rejected</span>',
                        default     => '<span class="badge bg-secondary">'.ucfirst($row->status).'</span>',
                    };
                }) 
                ->addColumn('actions', function ($row) {
                    $downloadUrl = route('shop.download-invoice', $row->id);
                    return '<a href="'.route('shop.returnOrder.show',$row->id).'" class="btn btn-primary btn-sm"><i class="fa fa-eye"></i></a>
                    <a href="'.$downloadUrl.'" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" data-bs-title="Download Invoice">  <i class="fa fa-download"></i> </a>';
                })
                ->rawColumns(['return_code', 'status_badge', 'actions'])
                ->toJson();
        }

        return view('admin.shop.return-orders', compact('shop'));
    }

    public function address(Shop $shop)
    { 
        return view('admin.shop.address', compact('shop'));
    }

    public function followers(Shop $shop)
    {
        $followers = ShopFollower::with(['customer.user'])
            ->where('shop_id', $shop->id)
            ->latest()
            ->paginate(10);

        return view('admin.shop.followers', compact('shop', 'followers'));
    }
}

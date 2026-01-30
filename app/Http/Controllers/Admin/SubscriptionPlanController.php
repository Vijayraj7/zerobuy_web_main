<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\ShopSubscription;
use App\Models\SubscriptionPlan;
use App\Models\Shop;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Repositories\ShopRepository;
use App\Http\Requests\SubscriptionPlanRequest;
use App\Repositories\ShopSubscriptionRepository;
use App\Repositories\SubscriptionPlanRepository;
use App\Models\OfflinePaymentDetail;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

class SubscriptionPlanController extends Controller
{
    public function index()
    {
        $subscriptionPlans = SubscriptionPlanRepository::query()->paginate(20);

        return view('admin.subscription-plan.index', compact('subscriptionPlans'));
    }

    public function create()
    {
        return view('admin.subscription-plan.create');
    }

    public function store(SubscriptionPlanRequest $request)
    {
        SubscriptionPlanRepository::storeByRequest($request);

        return to_route('admin.subscription-plan.index')->withSuccess(__('Created successfully'));
    }

    public function edit(SubscriptionPlan $subscriptionPlan)
    {
        return view('admin.subscription-plan.edit', compact('subscriptionPlan'));
    }

    public function update(SubscriptionPlanRequest $request, SubscriptionPlan $subscriptionPlan)
    {
        SubscriptionPlanRepository::updateByRequest($request, $subscriptionPlan);

        return to_route('admin.subscription-plan.index')->withSuccess(__('Updated successfully'));
    }

    public function statusToggle(SubscriptionPlan $subscriptionPlan)
    {
        $subscriptionPlan->update([
            'is_active' => ! $subscriptionPlan->is_active,
        ]);

        return back()->withSuccess(__('Status updated successfully'));
    }

    public function destroy(SubscriptionPlan $subscriptionPlan)
    {
        $subscriptionPlan->delete();

        return back()->withSuccess('deleted successfully');
    }

    public function subscriptionList(Request $request)
    {
        $shop = $request->shop;

        $subscriptions = ShopSubscription::when($shop, function ($query) use ($shop) {
            return $query->where('shop_id', $shop);
        })->latest()->paginate(20);

        $shops = ShopRepository::query()->isActive()->get();

        return view('admin.subscription-plan.list', compact('subscriptions', 'shops'));
    }

    public function subscriptionStatus(ShopSubscription $shopSubscription)
    {
        try {
            $saleLimit = $shopSubscription->sale_limit;
            $remainingSales = $shopSubscription->sale_limit;

            $currentSubscription = ShopSubscriptionRepository::query()
                ->where('shop_id', $shopSubscription->shop_id)
                ->where('status', SubscriptionStatus::ACTIVE->value)
                ->first();

            if ($currentSubscription) {
                if ($saleLimit && $currentSubscription->remaining_sales) {
                    $saleLimit = $saleLimit + $currentSubscription->remaining_sales;
                    $remainingSales = $saleLimit + $currentSubscription->remaining_sales;
                }

                $currentSubscription->update([
                    'status' => SubscriptionStatus::CANCELLED,
                ]);
            }
            $shopSubscription->update([
                'starts_at' => now(),
                'ends_at' => $shopSubscription->duration ? now()->addDays($shopSubscription->duration) : null,
                'sale_limit' => $saleLimit,
                'remaining_sales' => $remainingSales,
                'status' => SubscriptionStatus::ACTIVE,
            ]);
            return back()->withSuccess(__('Status updated successfully'));
        } catch (\Exception $e) {
            return back()->withError($e->getMessage());
        }
    }

    public function createShopSubscription()
    {
        return view('admin.subscription-plan.create-shop-subscription');
    }
    
    public function createOfflineDetails()
    {
        $offline = OfflinePaymentDetail::first(); 
        return view('admin.subscription-plan.create-offline-details', compact('offline'));
    }

    public function storeOfflineDetails(Request $request)
    {
        $request->validate([
            'account_name'   => 'required',
            'account_number' => 'required',
        ]);

        OfflinePaymentDetail::updateOrCreate(
            ['id' => $request->id],
            $request->only([
                'account_name',
                'account_number',
                'account_type',
                'ifsc_code',
                'branch_name',
                'upi_number',
            ])
        );

        return response()->json([
            'status' => true,
            'message' => 'Offline payment details saved successfully'
        ]);
    }

    public function subscriptionAllList(Request $request)
    {
        if ($request->ajax()) {
            $query = ShopSubscription::query()
                ->select('shop_subscriptions.*')
                ->whereIn('shop_subscriptions.id', function ($q) {
                    $q->selectRaw('MAX(id)')
                    ->from('shop_subscriptions')
                    ->groupBy('shop_id');
                })
                ->with(['shop:id,name', 'plan:id,name,duration']);
            
            /** STATUS FILTER */
            $now = Carbon::now();
            if ($request->status === 'Active') {
                $query->where('status', '!=', 'cancelled')
                    ->where('starts_at', '<=', $now)
                    ->where('ends_at', '>=', $now);
            }

            if ($request->status === 'Expired') {
                $query->where('status', '!=', 'cancelled')
                    ->where('ends_at', '<', $now);
            }

            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('activation_date', fn ($row) =>
                    optional($row->starts_at)->format('d-m-Y')
                )

                ->addColumn('shop_id', fn ($row) =>
                    'STR0' . $row->shop_id
                )

                ->addColumn('shop_name', fn ($row) =>
                    $row->shop->name ?? '-'
                )

                ->addColumn('subscription_plan_name', fn ($row) =>
                    $row->plan->name ?? '-'
                )

                ->addColumn('plan_validity', fn ($row) =>
                    $row->duration . ' Days'
                )

                ->addColumn('expiry_date', fn ($row) =>
                    optional($row->ends_at)->format('d-m-Y')
                ) 

                ->addColumn('remaining_days', function ($row) { 
                    if ($row->status === 'cancelled') {
                        return '_';
                    } 
                    if (empty($row->ends_at)) {
                        return '_';
                    }
                    // $now = Carbon::now();
                    // $endDate = Carbon::parse($row->ends_at); 
                    $endDate = Carbon::parse($row->ends_at)->startOfDay();
                    $today   = now()->startOfDay();

                    if ($endDate->lt($today)) {
                        return '0 Days';
                    } 
                    // $days = $now->diffInDays($endDate, false);
                    $days = $today->diffInDays($endDate, false);
                    return max(0, (int) $days) . ' Days'; 
                })

                ->addColumn('price', fn ($row) =>
                    number_format($row->price, 2)
                )
 
                ->addColumn('status', function ($row) {
 
                    if ($row->status === 'cancelled') {
                        return "<span class='badge bg-dark'>Cancelled</span>";
                    }

                    $now = Carbon::now();

                    if (is_null($row->starts_at)) {
                        return "<span class='badge bg-warning'>Pending</span>";
                    }

                    if ($row->starts_at->gt($now)) {
                        return "<span class='badge bg-info'>Upcoming</span>";
                    }

                    if ($row->starts_at->lte($now) && $row->ends_at && $row->ends_at->gte($now)) {
                        return "<span class='badge bg-success'>Active</span>";
                    }

                    if ($row->ends_at && $row->ends_at->lt($now)) {
                        return "<span class='badge bg-danger'>Expired</span>";
                    }

                    return "<span class='badge bg-secondary'>Unknown</span>";
                }) 

                ->addColumn('actions', fn ($row) =>
                    '<a href="'.route('admin.subscription-plan.subscription.history', $row->shop_id).'"
                        class="btn btn-sm btn-primary">
                        History
                    </a>'
                )


                ->rawColumns(['status', 'actions'])
                ->make(true);
        } 
        $now = Carbon::now();

        /** Active Stores */
        $activeCount = ShopSubscription::whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                ->from('shop_subscriptions')
                ->groupBy('shop_id');
            })
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->where('status', '!=', 'cancelled')
            ->count();

        /** Expired Stores */
        $expiredCount = ShopSubscription::whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                ->from('shop_subscriptions')
                ->groupBy('shop_id');
            })
            ->where('ends_at', '<', $now)
            ->where('status', '!=', 'cancelled')
            ->count();


        return view( 'admin.subscription-plan.subscription-store-list', compact('activeCount', 'expiredCount') );
    }

    public function subscriptionHistory($shopId)
    {
        $shop = Shop::findOrFail($shopId);

        $today = now()->startOfDay();

        $subscriptions = ShopSubscription::with('plan:id,name,duration')
            ->where('shop_id', $shopId)
            ->latest()
            ->get()
            ->map(function ($row) use ($today) {

                // ---------- STATUS ----------
                if ($row->status === 'cancelled') {
                    $status = 'Cancelled';
                } elseif (!$row->starts_at) {
                    $status = 'Pending';
                } elseif ($row->starts_at->gt($today)) {
                    $status = 'Upcoming';
                } elseif ($row->ends_at && $row->ends_at->gte($today)) {
                    $status = 'Active';
                } else {
                    $status = 'Expired';
                }

                // ---------- REMAINING DAYS ----------
                if ($row->status === 'cancelled' || !$row->ends_at) {
                    $remaining = '_';
                } else {
                    $endDate = Carbon::parse($row->ends_at)->startOfDay();
                    $today   = now()->startOfDay();

                    if ($endDate->lt($today)) {
                        $remaining = '0 Days';
                    } else {
                        $remaining = $today->diffInDays($endDate, false);
                        $remaining = max(0, (int) $remaining) . ' Days';
                    }
                }

                return [
                    'activation_date' => optional($row->starts_at)->format('d-m-Y'),
                    'plan'            => $row->plan->name ?? '-',
                    'validity'        => $row->duration . ' Days',
                    'remaining_days'  => $remaining,
                    'expiry_date'     => optional($row->ends_at)->format('d-m-Y'),
                    'amount'          => number_format($row->price, 2),
                    'status'          => $status,
                ];
            });

        return view(
            'admin.subscription-plan.subscription-history',
            compact('shop', 'subscriptions')
        );
    }

}

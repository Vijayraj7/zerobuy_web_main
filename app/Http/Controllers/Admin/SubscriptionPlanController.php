<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\ShopSubscription;
use App\Models\SubscriptionPlan;
use App\Models\Shop;
use App\Models\Payment;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Repositories\ShopRepository;
use App\Http\Requests\SubscriptionPlanRequest;
use App\Repositories\ShopSubscriptionRepository;
use App\Repositories\SubscriptionPlanRepository;
use App\Models\OfflinePaymentDetail;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        $subscriptionPlan->update([
            'is_active' => false,
        ]);

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
        $shops = Shop::query()
            ->select('id', 'name')
            ->withCount('currentSubscription')
            ->orderBy('name')
            ->get();

        $plans = SubscriptionPlanRepository::query()
            ->active()
            ->select('id', 'name', 'price', 'duration', 'sale_limit')
            ->orderBy('name')
            ->get();

        return view('admin.subscription-plan.create-shop-subscription', compact('shops', 'plans'));
    }

    public function currentShopSubscription(Shop $shop)
    {
        $subscription = $shop->currentSubscription()->with('plan:id,name')->first();

        if (! $subscription) {
            return response()->json([
                'status' => true,
                'has_active_subscription' => false,
                'message' => 'No active subscription found for this shop',
                'data' => null,
            ]);
        }

        $remainingDays = null;
        if ($subscription->ends_at) {
            $remainingDays = max(0, now()->startOfDay()->diffInDays($subscription->ends_at->startOfDay(), false));
        }

        return response()->json([
            'status' => true,
            'has_active_subscription' => true,
            'data' => [
                'plan_name' => $subscription->plan?->name,
                'price' => $subscription->price,
                'duration' => $subscription->duration,
                'starts_at' => optional($subscription->starts_at)->format('d-m-Y'),
                'ends_at' => optional($subscription->ends_at)->format('d-m-Y'),
                'sale_limit' => $subscription->sale_limit,
                'remaining_sales' => $subscription->remaining_sales,
                'remaining_days' => $remainingDays,
                'status' => $subscription->status,
            ],
        ]);
    }

    public function storeShopSubscription(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:shops,id',
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        DB::beginTransaction();

        try {
            $shop = Shop::query()->findOrFail($request->store_id);

            $subscriptionPlan = SubscriptionPlanRepository::query()
                ->active()
                ->findOrFail($request->plan_id);

            if (! $subscriptionPlan->canBePurchasedByShop($shop->id)) {
                DB::rollBack();

                return back()->withInput()->withError('This plan can only be purchased once per store');
            }

            $payment = Payment::create([
                'amount' => $subscriptionPlan->price,
                'payment_method' => 'manual_admin',
                'is_paid' => true,
            ]);

            $subscription = ShopSubscriptionRepository::create([
                'shop_id' => $shop->id,
                'plan_id' => $subscriptionPlan->id,
                'price' => $subscriptionPlan->price,
                'duration' => $subscriptionPlan->duration,
                'sale_limit' => $subscriptionPlan->sale_limit,
                'remaining_sales' => $subscriptionPlan->sale_limit,
                'payment_id' => $payment->id,
                'status' => SubscriptionStatus::PENDING->value,
            ]);

            $currentSubscription = ShopSubscriptionRepository::query()
                ->where('shop_id', $shop->id)
                ->where('status', SubscriptionStatus::ACTIVE->value)
                ->where('id', '!=', $subscription->id)
                ->first();

            $saleLimit = $subscription->sale_limit;
            $remainingSales = $subscription->sale_limit;
            $extraDays = 0;

            if ($currentSubscription) {
                if ($currentSubscription->remaining_sales) {
                    if ($saleLimit !== null) {
                        $saleLimit = $saleLimit + $currentSubscription->remaining_sales;
                    }
                    $remainingSales = ($remainingSales ?? 0) + $currentSubscription->remaining_sales;
                }

                if ($currentSubscription->ends_at && $currentSubscription->ends_at->gt(now())) {
                    $extraDays = (int) now()->diffInDays($currentSubscription->ends_at);
                }

                $currentSubscription->update([
                    'status' => SubscriptionStatus::CANCELLED->value,
                ]);
            }

            $totalDays = $subscription->duration ? (int) $subscription->duration + $extraDays : null;

            $subscription->update([
                'starts_at' => now(),
                'ends_at' => $totalDays ? now()->addDays($totalDays) : null,
                'sale_limit' => $saleLimit,
                'remaining_sales' => $remainingSales,
                'status' => SubscriptionStatus::ACTIVE->value,
            ]);

            DB::commit();

            return to_route('admin.subscription-plan.subscription.list')
                ->withSuccess(__('Subscription created successfully'));
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withError($e->getMessage());
        }
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

                ->addColumn(
                    'activation_date',
                    fn($row) =>
                    optional($row->starts_at)->format('d-m-Y')
                )

                ->addColumn(
                    'shop_id',
                    fn($row) =>
                    'STR0' . $row->shop_id
                )

                ->addColumn(
                    'shop_name',
                    fn($row) =>
                    $row->shop->name ?? '-'
                )

                ->addColumn(
                    'subscription_plan_name',
                    fn($row) =>
                    $row->plan->name ?? '-'
                )

                ->addColumn(
                    'plan_validity',
                    fn($row) =>
                    $row->duration . ' Days'
                )

                ->addColumn(
                    'expiry_date',
                    fn($row) =>
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

                ->addColumn(
                    'price',
                    fn($row) =>
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

                ->addColumn(
                    'actions',
                    fn($row) =>
                    '<a href="' . route('admin.subscription-plan.subscription.history', $row->shop_id) . '"
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


        return view('admin.subscription-plan.subscription-store-list', compact('activeCount', 'expiredCount'));
    }

    public function subscriptionHistory($shopId)
    {
        $shop = Shop::findOrFail($shopId);

        $today = now();

        $subscriptions = ShopSubscription::with('plan:id,name,duration')
            ->where('shop_id', $shopId)
            ->latest()
            ->get()
            ->map(function ($row) use ($today) {

                // ---------- STATUS ----------
                if ($row->status === 'cancelled') {
                    $status = 'Expired';
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

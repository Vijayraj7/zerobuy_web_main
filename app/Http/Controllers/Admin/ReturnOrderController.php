<?php

namespace App\Http\Controllers\Admin;

use App\Models\ReturnOrder;
use App\Models\Shop;
use Illuminate\Http\Request;
use App\Enums\ReturnOderStatus;
use App\Http\Controllers\Controller;
use App\Models\ReturnOrderStatusTimeline;
use App\Repositories\WalletRepository;
use App\Repositories\ReturnOrderRepository;
use App\Repositories\TransactionRepository;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class ReturnOrderController extends Controller
{
    private function allowedNextStatuses(string $currentStatus): array
    {
        return match ($currentStatus) {
            ReturnOderStatus::PENDING->value => [
                ReturnOderStatus::APPROVED->value,
                ReturnOderStatus::REJECTED->value,
            ],
            ReturnOderStatus::APPROVED->value => [
                ReturnOderStatus::COMPLETED->value,
            ],
            default => [],
        };
    }

    // public function index()
    // {
    //     $returnOrder = ReturnOrderRepository::query()->latest('id')->paginate(20);
    //     return view('admin.returnOrder.index', compact('returnOrder'));
    // }

    public function index(Request $request)
    {
        $query = ReturnOrder::query()
            ->with(['order:id,order_code,created_at', 'customer.user:id,name,phone', 'returnProduct']);

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
                ->editColumn('created_at', fn($row) => $row->created_at->format('d-m-Y | h:i A'))
                ->addColumn('return_id', function ($row) {
                    return $row->return_code ? $row->return_code : 'RTN0' . $row->id;
                })
                ->filterColumn('return_id', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('return_code', 'LIKE', "%{$keyword}%");
                        $num = preg_replace('/\D/', '', $keyword);
                        if ($num) {
                            $q->orWhere('id', 'LIKE', "%{$num}%");
                        }
                    });
                })
                ->addColumn('order_id', function ($row) {
                    return optional($row->order)->order_code ? optional($row->order)->order_code : (optional($row->order)->id ? 'ORD0' . optional($row->order)->id : '-');
                })
                ->addColumn('thumbnail', function ($row) {
                    $first = optional($row->returnProduct->first());
                    // try product->thumbnail then fallback to returnProduct->thumbnail
                    $thumb = optional($first->product)->thumbnail ?? ($first?->thumbnail ?? null);
                    if (! $thumb) return '';
                    return '<img src="'.$thumb.'" width="50" class="rounded">';
                })
                ->filterColumn('order_id', function ($query, $keyword) {
                    $query->whereHas('order', function ($q) use ($keyword) {
                        $q->where('order_code', 'LIKE', "%{$keyword}%");
                        $num = preg_replace('/\D/', '', $keyword);
                        if ($num) {
                            $q->orWhere('id', 'LIKE', "%{$num}%");
                        }
                    });
                })
                ->addColumn('shop_id', function ($row) {
                    return optional($row->shop)->shop_code ? optional($row->shop)->shop_code : 'STR0' . $row->shop_id;
                })
                ->filterColumn('shop_id', function ($query, $keyword) {
                    $query->whereHas('shop', function ($q) use ($keyword) {
                        $q->where('shop_code', 'LIKE', "%{$keyword}%");
                        $num = preg_replace('/\D/', '', $keyword);
                        if ($num) {
                            $q->orWhere('id', 'LIKE', "%{$num}%");
                        }
                    });
                })
                ->addColumn('shop_name', fn($row) => $row->shop?->name ?? '-')
                ->filterColumn('shop_name', function ($query, $keyword) {
                    $query->whereHas('shop', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
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
                ->orderColumn('order_id', function ($query, $order) {
                    $query->join('orders', 'return_orders.order_id', '=', 'orders.id')
                        ->orderBy('orders.id', $order)
                        ->select('return_orders.*');
                })
                ->addColumn('customer_phone', fn($row) => $row->customer?->user?->phone ?? '-') 
                ->addColumn('quantity', fn($row) => $row->returnProduct->sum('quantity')) 
                ->addColumn('total', fn($row) =>
                    number_format($row->amount ?? 0, 2)
                )
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
                    return '<a href="'.route('admin.returnOrder.show',$row->id).'" class="btn btn-primary btn-sm"><i class="fa fa-eye"></i></a>'; 
                })
                ->rawColumns(['status_badge', 'actions', 'thumbnail'])
                ->toJson();
        }

        return view('admin.returnOrder.index');
    }

    public function show(ReturnOrder $returnOrder)
    {
        $returnOrder->load('order.address.stateData', 'order.address.districtData', 'returnProduct.product', 'returnProductImages');

        $allowedNextStatuses = $this->allowedNextStatuses((string) $returnOrder->status);

        return view('admin.returnOrder.show', compact('returnOrder', 'allowedNextStatuses'));
    }

    public function statusChange(ReturnOrder $returnOrder, Request $request)
    {
        $shopId   = auth()->user()->shop->id;
        $rootShop = generaleSetting('rootShop');

        if (!$rootShop && $returnOrder->shop_id != $shopId) {
            abort(404);
        }

        if ($returnOrder->payment_status == 1) {
            return back()->with('error', __('Already paid for this order'));
        }

        $allowedNextStatuses = $this->allowedNextStatuses((string) $returnOrder->status);

        if (empty($allowedNextStatuses)) {
            return back()->with('error', __('Status cannot be changed from current state'));
        }

        $request->validate([
            'status' => ['required', Rule::in($allowedNextStatuses)],
        ]);

        $previousStatus = (string) $returnOrder->status;
        $returnOrder->update(['status' => $request->status]);

        $isNowRestockEligible = in_array((string) $request->status, [
            ReturnOderStatus::APPROVED->value,
            ReturnOderStatus::COMPLETED->value,
        ], true);
        $wasAlreadyRestocked = in_array($previousStatus, [
            ReturnOderStatus::APPROVED->value,
            ReturnOderStatus::COMPLETED->value,
        ], true);

        if ($isNowRestockEligible && !$wasAlreadyRestocked) {
            ReturnOrderRepository::restockReturnedProducts($returnOrder);
        }

        ReturnOrderStatusTimeline::updateOrCreate(
            [
                'return_order_id' => $returnOrder->id,
                'status' => $request->status,
            ],
            [
                'changed_at' => Carbon::now(),
            ]
        );

        return back()->with('success', __('Status updated successfully'));
    }

    public function paymentStatus(ReturnOrder $returnOrder)
    {
        if ($returnOrder->status == 'Pending') {
            return back()->with('error', __('Return order is not approved yet'));
        }
        if (in_array($returnOrder->status, ['Cancelled', 'Rejected'], true)) {
            return back()->with('error', __('Return order is not eligible for payment update'));
        }
        if ($returnOrder->payment_status == 1) {
            return back()->with('error', __('Payment status updated successfully'));
        }

        $returnOrder->update(['payment_status' => 1]);

        $this->updateWalletAndTransaction($returnOrder);

        return back()->with('success', __('Payment status updated successfully'));
    }


    private function updateWalletAndTransaction($returnOrder)
    {

        $generaleSetting = generaleSetting('setting');

        $commission = 0;

        if ($generaleSetting?->commission_charge != 'monthly') {

            if ($generaleSetting?->commission_type != 'fixed') {
                $commission = $returnOrder->amount * $generaleSetting->commission / 100;
            } else {
                $commission = $generaleSetting->commission ?? 0;
            }
        }
        $amount = $returnOrder->amount;

        $wallet = $returnOrder->shop->user->wallet;

        WalletRepository::updateByRequest($wallet, $amount, 'debit');

        TransactionRepository::storeByRefundRequest($wallet, $commission, 'credit', true, true, 'admin commission removal for refund order', 'refundorder', null, $returnOrder->id);
    }

    public function returnReject(ReturnOrder $returnOrder, Request $request)
    {
        $returnOrder->update([
            'status' => $request->status,
            'reject_note' => $request->reject_note
        ]);
        ReturnOrderStatusTimeline::updateOrCreate(
            [
                'return_order_id' => $returnOrder->id,
                'status' => $request->status,
            ],
            [
                'changed_at' => Carbon::now(),
            ]
        );
        return back()->with('success', __('Return order Cancelled successfully'));
    }
}

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

class ReturnOrderController extends Controller
{
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
                ->editColumn('created_at', fn($row) => $row->created_at->format('d-m-Y')) 
                ->addColumn('return_id', fn($row) => 'RTN0' . $row->id)
                ->filterColumn('return_id', function ($query, $keyword) {
                    $keyword = str_replace('RTN0', '', $keyword);
                    $query->where('id', 'LIKE', "%$keyword%");
                })  
                ->addColumn('order_date', fn($row) => optional($row->order)->created_at?->format('d-m-Y') ?? '-') 
                ->addColumn('shop_id', fn($row) => 'STR0' . $row->shop_id)
                ->filterColumn('shop_id', function ($query, $keyword) {
                    $keyword = str_replace('STR0', '', $keyword);
                    $query->where('shop_id', 'LIKE', "%$keyword%");
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
                ->addColumn('customer_phone', fn($row) => $row->customer?->user?->phone ?? '-') 
                ->addColumn('quantity', fn($row) => $row->returnProduct->sum('quantity')) 
                // ->addColumn('amount', fn($row) => number_format($row->returnProduct->sum(fn($p) => $p->price * $p->quantity), 2)) 
                // ->editColumn('amount', fn($row) => number_format($row->amount, 2)) 
                ->addColumn('amount', fn($row) =>
                    number_format(optional($row->returnProduct->first())->price ?? 0, 2)
                )
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
                ->rawColumns(['status_badge', 'actions'])
                ->toJson();
        }

        return view('admin.returnOrder.index');
    }

    public function show(ReturnOrder $returnOrder)
    {
        $returnOrder->load('order.address.stateData', 'order.address.districtData', 'returnProduct.product', 'returnProductImages');

        $returnStatus = ReturnOderStatus::cases();
        return view('admin.returnOrder.show', compact('returnOrder', 'returnStatus'));
    }

    public function statusChange(ReturnOrder $returnOrder, Request $request)
    {
        $request->validate(['status' => 'required']);

        $shopId   = auth()->user()->shop->id;
        $rootShop = generaleSetting('rootShop');

        if (!$rootShop && $returnOrder->shop_id != $shopId) {
            abort(404);
        }

        if ($returnOrder->payment_status == 1) {
            return back()->with('error', __('Already paid for this order'));
        }

        $returnOrder->update(['status' => $request->status]);

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
        if ($returnOrder->status == 'Cancelled') {
            return back()->with('error', __('Return order is Cancelled'));
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

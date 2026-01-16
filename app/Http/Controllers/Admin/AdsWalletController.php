<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DataTables;

class AdsWalletController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $wallets = Wallet::with([
                'shop.products',
                'shop.orders',
                'shop.currentSubscription'
            ]);

            if ($request->start_date) {
                $wallets->whereDate('created_at','>=',$request->start_date);
            }

            if ($request->end_date) {
                $wallets->whereDate('created_at','<=',$request->end_date);
            }

            return DataTables::eloquent($wallets)
                ->addIndexColumn()

                ->addColumn('create_date', function ($w) {
                    return optional($w->created_at)->format('d-m-Y | h:i A') ?? '—';
                }) 

                ->addColumn('store_id', fn($w) =>
                    $w->shop ? 'STR0'.$w->shop->id : '—'
                )

                ->addColumn('store_name', fn($w) =>
                    $w->shop->name ?? '—'
                )

                ->addColumn('state', fn($w) =>
                    $w->shop->state ?? '—'
                )

                ->addColumn('total_products', fn($w) =>
                    $w->shop?->products?->count() ?? 0
                )

                ->addColumn('total_orders', fn($w) =>
                    $w->shop?->orders?->count() ?? 0
                )

                ->addColumn('subscription', function ($w) {
                    $sub = $w->shop?->currentSubscription;
                    if (!$sub) return '—';
                    return $sub->starts_at->diffInDays($sub->ends_at).' Days';
                })

                ->addColumn('wallet_amount', fn($w) =>
                    '₹ '.number_format($w->balance,2)
                )

                ->addColumn('status', fn() =>
                    '<span class="badge bg-success">Active</span>'
                )

                ->addColumn('actions', fn($w) => '
                    <a href="'.route('admin.wallet.transactions',$w->id).'" 
                       class="btn btn-sm btn-outline-primary">
                        <i class="fa fa-list"></i>
                    </a>
                    <button class="btn btn-sm btn-outline-success rechargeBtn"
                        data-id="'.$w->id.'" data-balance="'.$w->balance.'">
                        <i class="fa fa-plus"></i>
                    </button>
                ')

                ->rawColumns(['status','actions'])
                ->make(true);
        }

        return view('admin.ads-wallet.index');
    } 

    public function recharge(Request $request)
    {
        $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'amount'    => 'required|numeric|min:1',
            'type'      => 'required|in:credit,debit'
        ]);

        $wallet = Wallet::findOrFail($request->wallet_id);

        if ($request->type === 'debit' && $request->amount > $wallet->balance) {
            return response()->json([
                'message' => 'Debit amount cannot exceed wallet balance'
            ], 422);
        }

        DB::transaction(function () use ($request, $wallet) {

            if ($request->type === 'credit') {
                $wallet->increment('balance', $request->amount);
            } else {
                $wallet->decrement('balance', $request->amount);
            }

            Transaction::create([
                'wallet_id'      => $wallet->id,
                'amount'         => $request->amount,
                'type'           => $request->type, // credit / debit
                'is_commission'  => 0,
                'transaction_id' => 'TXN-' . strtoupper(Str::random(10)),
                'purpose'        => $request->type === 'credit'
                                    ? 'Wallet Recharge by Admin'
                                    : 'Wallet Adjustment by Admin',
                'note'           => $request->type === 'credit'
                                    ? 'Wallet Recharge'
                                    : 'Wallet Debit (Correction)'
            ]);
        });

        return response()->json([
            'success' => true,
            'balance' => $wallet->fresh()->balance
        ]);
    }


    public function transactions(Request $request, Wallet $wallet)
    {
        if ($request->ajax()) {

            $transactions = Transaction::where('wallet_id', $wallet->id);

            return DataTables::eloquent($transactions)
                ->addIndexColumn()

                ->addColumn('date', fn ($t) =>
                    optional($t->created_at)->format('d-m-Y h:i A') ?? '—'
                )

                ->addColumn('amount', fn ($t) =>
                    '₹ ' . number_format($t->amount, 2)
                )

                ->addColumn('status', fn ($t) =>
                    $t->type === 'credit'
                        ? '<span class="badge bg-success">Credit</span>'
                        : '<span class="badge bg-danger">Debit</span>'
                )

                ->rawColumns(['status'])
                ->make(true);
        }

        return view('admin.ads-wallet.transactions', compact('wallet'));
    }
}

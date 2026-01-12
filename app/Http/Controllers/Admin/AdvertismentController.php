<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Advertisement;
use App\Models\Product;
use App\Models\Wallet;
use App\Models\Transaction; 
use Carbon\Carbon;
use DataTables;
use DB;

class AdvertismentController extends Controller
{
    public function index(Request $request)
    {
        $shop = generaleSetting('shop');
        $wallet = Wallet::where('user_id', $shop->user_id)->first();

        if ($request->ajax()) {
            $ads = Advertisement::with('product')->where('shop_id', $shop->id);

            return DataTables::of($ads)
                ->addIndexColumn()
                ->addColumn( 'start_date', fn($row) => $row->start_date?->format('d-m-Y | h:i A') )
                ->addColumn( 'end_date', fn($row) => $row->end_date?->format('d-m-Y | h:i A') )
                ->addColumn('product_image', fn($row) =>
                    $row->product ? '<img src="'.asset($row->product->thumbnail).'" width="40">' : 'N/A'
                ) 
                ->addColumn('product_id', fn($row) => $row->product_id ? 'PRD0' . $row->product_id : 'N/A')

                ->filterColumn('product_id', function ($query, $keyword) {
                    $keyword = str_replace('PRD0', '', $keyword);
                    $query->where('product_id', 'LIKE', "%$keyword%");
                })
                ->orderColumn( 'product_id', fn($query, $keyword) => $query->orderBy('product_id', $keyword) )
                
                ->addColumn('product_name', fn($row) => $row->product?->name ?? 'N/A')


                ->addColumn('status', fn($row) => $row->status === 'active'
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Completed</span>'
                )
                ->rawColumns(['product_image', 'start_date', 'end_date', 'status'])
                ->make(true);
        }

        return view('admin.advertisement.index', compact('wallet'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ads_type'     => 'required',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after_or_equal:start_date',
            'daily_budget' => 'required|numeric|min:1',
            'product_id'   => 'required_if:ads_type,product',
        ]);

        $shop   = generaleSetting('shop');
        $wallet = Wallet::where('user_id', $shop->user_id)->first();

        $days = Carbon::parse($request->start_date)
            ->diffInDays(Carbon::parse($request->end_date)) + 1;

        $totalBudget = $days * $request->daily_budget;

        if ($wallet->balance < $totalBudget) {
            return response()->json([
                'message' => 'Insufficient wallet balance'
            ], 400);
        }

        DB::transaction(function () use ($request, $shop, $wallet, $totalBudget) {

            Advertisement::create([
                'shop_id'      => $shop->id,
                'ads_type'     => $request->ads_type,
                'product_id'   => $request->ads_type === 'product' ? $request->product_id : null,
                'start_date'   => $request->start_date,
                'end_date'     => $request->end_date,
                'daily_budget' => $request->daily_budget,
                'total_budget' => $totalBudget,
            ]);

            $wallet->decrement('balance', $totalBudget);

            Transaction::create([
                'wallet_id' => $wallet->id,
                'amount'    => $totalBudget,
                'type'      => 'debit',
                'purpose'   => 'Ads Run',
                'note'      => 'Advertisement',
            ]);
        });

        return response()->json([
            'message' => 'Advertisement created successfully'
        ]);
    }

    public function products(Request $request)
    {
        $shop = generaleSetting('shop');

        return Product::where('shop_id', $shop->id)
            ->where('is_active', 1)
            ->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->q}%")
                  ->orWhere('id', 'like', "%{$request->q}%");
            })
            ->get()
            ->map(fn ($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'price' => $p->price,
                'image' => asset($p->thumbnail),
            ]);
    }


    public function transactions()
    {
        $shop   = generaleSetting('shop');
        $wallet = Wallet::where('user_id', $shop->user_id)->first();

        $transactions = Transaction::where('wallet_id', $wallet->id)
            ->where('purpose', 'Ads Run')
            ->latest()
            ->get();

        return view('admin.advertisement.transaction-list', compact('transactions'));
    }
} 

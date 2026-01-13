<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; 
use App\Models\Certificate;
use App\Models\Shop;
use App\Models\Product;
use App\Models\Order;
use App\Models\ShopSubscription; 
use App\Repositories\MediaRepository;  
use Yajra\DataTables\DataTables;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $certificates = Certificate::with(['shop', 'media'])->latest();

            if ($request->startDate) {
                $certificates->whereDate('certificates.created_at', '>=', $request->startDate);
            }
            if ($request->endDate) {
                $certificates->whereDate('certificates.created_at', '<=', $request->endDate);
            }

            return DataTables::of($certificates)
            
                ->addIndexColumn()

                ->addColumn('create_date', fn($row) =>
                    $row->created_at->format('d M Y')
                ) 
                
                ->addColumn('store_id', function ($row) {
                    return 'STR0' . $row->shop_id;
                })

                ->filterColumn('store_id', function ($query, $keyword) {
                    $keyword = str_replace('STR0', '', $keyword);
                    $query->where('certificates.shop_id', 'LIKE', "%{$keyword}%");
                })

                ->orderColumn('store_id', function ($query, $order) {
                    $query->orderBy('certificates.shop_id', $order);
                })

                ->addColumn('store_name', fn($row) => $row->shop->name)

                ->addColumn('state', fn($row) => $row->shop->state)

                ->addColumn('total_products', function ($row) {
                    return Product::where('shop_id', $row->shop_id)->count();
                })

                ->addColumn('total_orders', function ($row) {
                    return Order::where('shop_id', $row->shop_id)->count();
                })

                ->addColumn('subscription', function ($row) {
                    $subscription = $row->shop->currentSubscription()->with('plan')->first();
                    $daysLeft = 0;
                    $totalDays = 0;

                    if ($subscription && $subscription->ends_at && $subscription->starts_at) { 
                        $daysLeft = max(0, now()->startOfDay()->diffInDays($subscription->ends_at->startOfDay()));
                        $totalDays = $subscription->starts_at->diffInDays($subscription->ends_at);
                    }
                    return $totalDays . ' Days';
                })

                ->addColumn('certificate_image', function ($row) {
                    if (!$row->media) return '-';

                    return '<img src="' . asset($row->thumbnail) . '" width="60">';
                })

                ->addColumn('status', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return ' 
                        <label class="switch mb-0">
                            <input type="checkbox" class="toggle-status" data-id="'.$row->id.'" '.$checked.'>
                            <span class="slider round"></span>
                        </label>';
                })

                ->addColumn('actions', function ($row) {
                    return '
                        <button class="btn btn-sm btn-primary editBtn" data-id="'.$row->id.'">
                            <i class="fa fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger deleteBtn" data-id="'.$row->id.'">
                            <i class="fa fa-trash"></i>
                        </button>';
                })

                ->rawColumns(['certificate_image', 'status', 'store_id', 'actions'])
                ->make(true);
        }

        $stores = Shop::select('id', 'name')->get();

        return view('admin.certificate.index', compact('stores'));
    } 

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'shop_id'     => 'required|exists:shops,id',
    //         'certificate' => $request->id ? 'nullable|image|max:2048' : 'required|image|max:2048',
    //     ]);
 
    //     $exists = Certificate::where('shop_id', $request->shop_id)
    //         ->when($request->id, fn($q) => $q->where('id', '!=', $request->id))
    //         ->exists();

    //     if ($exists) {
    //         return response()->json([
    //             'message' => 'Certificate already exists for this store'
    //         ], 422);
    //     }

    //     $mediaId = null;

    //     if ($request->hasFile('certificate')) {
    //         $file = MediaRepository::storeByRequest(
    //             $request->file('certificate'),
    //             'certificates',
    //             'image'
    //         );
    //         $mediaId = $file->id;
    //     }

    //     Certificate::updateOrCreate(
    //         ['id' => $request->id],
    //         [
    //             'shop_id'  => $request->shop_id,
    //             'media_id' => $mediaId,
    //             'status'   => 1
    //         ]
    //     );

    //     return response()->json(['message' => 'Certificate saved successfully']);
    // } 
    public function store(Request $request)
{
    $request->validate([
        'shop_id'     => 'required|exists:shops,id',
        'certificate' => $request->id
            ? 'nullable|image|max:2048'
            : 'required|image|max:2048',
    ]);

    $exists = Certificate::where('shop_id', $request->shop_id)
        ->when($request->id, fn ($q) => $q->where('id', '!=', $request->id))
        ->exists();

    if ($exists) {
        return response()->json([
            'message' => 'Certificate already exists for this store'
        ], 422);
    }

    $certificate = Certificate::find($request->id);

    $mediaId = $certificate?->media_id; // ✅ keep old image

    if ($request->hasFile('certificate')) {
        $file = MediaRepository::storeByRequest(
            $request->file('certificate'),
            'certificates',
            'image'
        );
        $mediaId = $file->id;
    }

    Certificate::updateOrCreate(
        ['id' => $request->id],
        [
            'shop_id'  => $request->shop_id,
            'media_id' => $mediaId,
            'status'   => 1
        ]
    );

    return response()->json(['message' => 'Certificate saved successfully']);
}


    public function edit(Certificate $certificate)
    {
        return response()->json([
            'id'       => $certificate->id,
            'shop_id'  => $certificate->shop_id,
            'image'    => $certificate->media ? asset($certificate->thumbnail) : null
        ]);
    }

    public function destroy(Certificate $certificate)
    {
        $certificate->delete();
        return response()->json(['message' => 'Certificate deleted']);
    }

    public function status(Certificate $certificate)
    {
        $certificate->update(['status' => !$certificate->status]);
        return response()->json(['message' => 'Status updated']);
    }
}

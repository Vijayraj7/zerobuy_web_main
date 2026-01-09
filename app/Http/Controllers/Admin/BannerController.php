<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BannerRequest;
use App\Models\Banner;
use App\Models\BusinessCategory;
use App\Models\SubCategory;
use App\Models\ChildCategory;
use App\Models\Product;
use App\Models\Shop;
use App\Repositories\BannerRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * Display a listing of the banners.
     */
    public function index()
    {
        // $rootShop = generaleSetting('rootShop'); 
        // $banners = Banner::whereNull('shop_id')->orWhere('shop_id', $rootShop->id)->paginate(20);
        // return view('admin.banner.index', compact('banners'));

        $banners = Banner::with(['media', 'businessCategory'])->latest()->paginate(20);
        $businessCategories = BusinessCategory::where('status', 1)->get();

        return view('admin.banner.index', compact('banners', 'businessCategories'));
    }

    /**
     * create new banner
     */
    public function create()
    {
        return view('admin.banner.create');
    }

    /**
     * store a new banner
     */
    public function store(BannerRequest $request)
    {
        BannerRepository::storeByRequest($request);

        // return to_route('admin.banner.index')->withSuccess(__('Banner created successfully'));
        return back()->withSuccess(__('Banner created successfully'));
    }

    /**
     * edit a banner
     */
    public function edit(Banner $banner)
    {
        return view('admin.banner.edit', compact('banner'));
    }

    /**
     * update a banner
     */
    public function update(BannerRequest $request, Banner $banner)
    {
        BannerRepository::updateByRequest($request, $banner);

        // return to_route('admin.banner.index')->withSuccess(__('Banner updated successfully'));
        return back()->withSuccess(__('Banner updated successfully'));
    }

    public function show(Banner $banner)
    {
        // dd($banner->thumbnail);
        // return response()->json($banner);
        return response()->json([
            'id'                        => $banner->id,
            'shop_id'                   => $banner->shop_id, 
            'business_category_id'      => $banner->business_category_id,
            'slider_position'           => $banner->slider_position, 
            'slider_type'               => $banner->slider_type, 
            'thumbnail'                 => $banner->thumbnail,
        ]);
    }

    /**
     * status toggle a banner
     */
    public function statusToggle(Banner $banner)
    {
        $banner->update([
            'status' => ! $banner->status,
        ]);

        return to_route('admin.banner.index')->withSuccess(__('Banner status updated'));
    }

    /**
     * destroy a banner
     */
    public function destroy(Banner $banner)
    {
        // delete banner
        $media = $banner->media;
        if (Storage::exists($media->src)) {
            Storage::delete($media->src);
        }
        $banner->delete();
        $media->delete();

        return to_route('admin.banner.index')->withSuccess(__('Banner deleted successfully'));
    } 
    
    public function sliderOptions(Request $request)
    {
        $type = $request->type;
        $businessCategoryId = $request->business_category_id;
        $search = $request->search;

        if (!$type || !$businessCategoryId) {
            return response()->json([]);
        }

        return match ($type) {

            /* ================= SUB CATEGORY ================= */
            'sub_category' =>
                SubCategory::where('business_category_id', $businessCategoryId)
                    ->when($search, fn ($q) =>
                        $q->where('name', 'like', "%{$search}%")
                    )
                    ->select('id', 'name')
                    ->get(),

            /* ================= CHILD CATEGORY ================= */
            'child_category' =>
                ChildCategory::where('business_category_id', $businessCategoryId)
                    ->when($search, fn ($q) =>
                        $q->where('name', 'like', "%{$search}%")
                    )
                    ->select('id', 'name')
                    ->get(),

            /* ================= PRODUCT ================= */
            'product' =>
                Product::whereHas('categories', function ($q) use ($businessCategoryId) {
                    $q->where('business_category_id', $businessCategoryId);
                })
                ->when($search, function ($q) use ($search) {
                    $id = (int) filter_var($search, FILTER_SANITIZE_NUMBER_INT);

                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('id', $id);
                })
                ->select('id', 'name')
                ->get()
                ->map(fn ($p) => [
                    'id'   => $p->id,
                    'name' => 'PRD' . str_pad($p->id, 3, '0', STR_PAD_LEFT) . ' - ' . $p->name,
                ]),

            /* ================= STORE ================= */
            'shop' =>
                Shop::whereHas('businessCategories', function ($q) use ($businessCategoryId) {
                    $q->where('business_category_id', $businessCategoryId);
                })
                ->when($search, function ($q) use ($search) {
                    $id = (int) filter_var($search, FILTER_SANITIZE_NUMBER_INT);

                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('id', $id);
                })
                ->select('id', 'name')
                ->get()
                ->map(fn ($s) => [
                    'id'   => $s->id,
                    'name' => 'STR' . str_pad($s->id, 3, '0', STR_PAD_LEFT) . ' - ' . $s->name,
                ]),

            default => [],
        };
    } 

}

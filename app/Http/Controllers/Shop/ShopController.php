<?php

namespace App\Http\Controllers\Shop;

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
    public function create()
    {
        $states = State::orderBy('name')->get();

        $sellerTerms = Page::where('slug', 'seller-terms-of-service')->where('is_active', 1)->first();
        $businessCategories = BusinessCategory::where('status', 1)->get();
        return view('admin.shop.create-edit', [
            'states' => $states,
            'businessCategories' => $businessCategories,
            'sellerTerms' => $sellerTerms,
            'formAction' => route('shop.shop.store'),
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
            'redirect' => route('shop.profile.index')
        ]);
    }

    public function update(Request $request, Shop $shop)
    {
        if (app()->environment() == 'local' && $shop->user->email == 'shop@readyecommerce.com') {
            return back()->with('demoMode', 'You can not update the shop in demo mode');
        }
        // dd($request->all());
        ShopRepository::updateByRequest($shop, $request);

        return response()->json([
            'status'   => 'success',
            'message'  => 'Shop updated successfully',
            'redirect' => route('shop.profile.index')
        ]);
    }
}

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
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * Display a listing of the shops.
     */
    public function index()
    {
        $shops = Shop::paginate(20);

        return view('admin.shop.index', compact('shops'));
    }

    /**
     * Create a new shop.
     */
    public function create()
    {
        // return view('admin.shop.create');
        $states = State::orderBy('name')->get();
        $sellerTerms = Page::where('slug', 'seller-terms-of-service')
                        ->where('is_active', 1)
                        ->first();
        $businessCategories = BusinessCategory::where('status', 1)->get();
        return view('admin.shop.create-edit', compact('states','businessCategories','sellerTerms'));
    }

    /**
     * Store a newly created shop.
     */ 
    public function store(ShopCreateRequest $request)
    { 
        if ($request->terms_condition_status != 1) {
            return response()->json([
                'status' => 'terms_required'
            ]);
        }

        ShopRepository::storeByRequest($request); 

        return response()->json([
            'status'   => 'success',
            'message'  => 'Shop created successfully',
            'redirect' => route('admin.shop.index')
        ]);

    }


    /**
     * Display the specified shop.
     */
    public function show(Shop $shop)
    {
        Notification::where('url', '/admin/shops/' . $shop->id)->whereNull('shop_id')->where('is_read', false)->update(['is_read' => true]);

        return view('admin.shop.show', compact('shop'));
    }

    /**
     * Edit the shop.
     */
    public function edit(Shop $shop)
    {
        // return view('admin.shop.edit', compact('shop'));
        $shop->load('deliverySetting');
        $states = State::orderBy('name')->get();
        $businessCategories = BusinessCategory::where('status', 1)->get();
        return view('admin.shop.create-edit', compact('shop', 'states','businessCategories'));
    }

    /**
     * Update the shop.
     */
    public function update(Request $request, Shop $shop)
    {
        if (app()->environment() == 'local' && $shop->user->email == 'shop@readyecommerce.com') {
            return back()->with('demoMode', 'You can not update the shop in demo mode');
        }

        // store shop from shopRepository
        ShopRepository::updateByRequest($shop, $request); 

        return response()->json([
            'status'   => 'success',
            'message'  => 'Shop updated successfully',
            'redirect' => route('admin.shop.index')
        ]);

    }

    /**
     * Toggle the status of the shop user.
     */
    public function statusToggle(Shop $shop)
    {
        if (app()->environment() == 'local' && $shop->user->email == 'shop@readyecommerce.com') {
            return back()->with('demoMode', 'You can not update status of the shop in demo mode');
        }

        $user = $shop->user;
        if ($user->hasRole('root')) {
            return back()->with('error', __('You can not update status of the root shop'));
        }

        // Update the user status
        $shop->user()->update([
            'is_active' => ! $shop->user->is_active,
        ]);

        return back()->withSuccess(__('Status updated successfully'));
    }

    /**
     * Toggle the status of the shop user.
     */
    public function brandedToggle(Shop $shop)
    {
        if (app()->environment() == 'local' && $shop->user->email == 'shop@readyecommerce.com') {
            return back()->with('demoMode', 'You can not update status of the shop in demo mode');
        }

        // $user = $shop->user;
        // if ($user->hasRole('root')) {
        //     return back()->with('error', __('You can not update status of the root shop'));
        // }

        // Update the user status
        $shop->update([
            'is_branded' => ! $shop->is_branded,
        ]);

        return back()->withSuccess(__('Branded updated successfully'));
    }

    /**
     * Toggle the status of the shop user.
     */
    public function verifyToggle(Shop $shop)
    {
        if (app()->environment() == 'local' && $shop->user->email == 'shop@readyecommerce.com') {
            return back()->with('demoMode', 'You can not update status of the shop in demo mode');
        }

        // $user = $shop->user;
        // if ($user->hasRole('root')) {
        //     return back()->with('error', __('You can not update status of the root shop'));
        // }

        // Update the user status
        $shop->update([
            'is_verified' => ! $shop->is_verified,
        ]);

        return back()->withSuccess(__('Verified updated successfully'));
    }

    /**
     * Display the shop orders.
     */
    public function orders(Shop $shop)
    {
        $orders = $shop->orders()->paginate(20);

        return view('admin.shop.orders', compact('shop', 'orders'));
    }

    /**
     * Display the shop products.
     */
    public function products(Shop $shop)
    {
        $products = $shop->products()->paginate(20);

        return view('admin.shop.products', compact('shop', 'products'));
    }

    /**
     * Display the shop category.
     */
    public function categories(Shop $shop)
    {
        $categories = $shop->categories()->paginate(20);

        return view('admin.shop.category', compact('shop', 'categories'));
    }

    /**
     * Display the shop reviews.
     */
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

        // Update the user status
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
}

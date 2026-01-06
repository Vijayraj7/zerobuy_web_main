<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\BlogController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\HomeController;
use App\Http\Controllers\API\ShopController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\BannerController;
use App\Http\Controllers\API\CouponController;
use App\Http\Controllers\API\MasterController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\AddressController;
use App\Http\Controllers\API\CountryController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\SupportController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\FlashSaleController;
use App\Http\Controllers\API\LegalPageController;
use App\Http\Controllers\API\SocialAuthController;
use App\Http\Controllers\API\SupportTicketController;
use App\Http\Controllers\Admin\WhatsAppChatController;
use App\Http\Controllers\API\TicketIssueTypeController;
use App\Http\Controllers\API\Auth\ForgotPasswordController;
use App\Http\Controllers\API\BusinessCategoryController;
use App\Http\Controllers\API\ReturnOrderController;
use App\Http\Controllers\API\ShopFollowerController;
use App\Http\Controllers\API\SubCategoryController;
use App\Http\Controllers\API\SupportTicketMessageController;
use App\Http\Controllers\API\ChildCategoryController;
use App\Http\Controllers\API\LocationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// auth token route
Route::controller(SocialAuthController::class)->group(function () {
    Route::post('/social-auth', 'login');
    Route::post('/auth/{provider}/token', 'handleTokenExchange');
});

// country route
Route::controller(CountryController::class)->group(function () {
    Route::get('/countries', 'index');
});

Route::controller(AuthController::class)->middleware('throttle:5,5')->group(function () {
    Route::post('/registration', 'register');
    Route::post('/login', 'login');
});

Route::controller(ForgotPasswordController::class)->group(function () {
    Route::post('/send-otp', 'resendOTP');
    Route::post('/verify-otp', 'verifyOtp');
    Route::post('/reset-password', 'resetPassword');
});

// legal page route
Route::controller(LegalPageController::class)->group(function () {
    Route::get('/legal-pages/{slug}', 'index');
    Route::get('/contact-us', 'contactUs');
});

// support route
Route::controller(SupportController::class)->group(function () {
    Route::post('/support', 'store');
});

// master route
Route::controller(MasterController::class)->group(function () {
    Route::get('/master', 'index');
});

// home route
Route::controller(HomeController::class)->group(function () {
    Route::get('/home', 'index');
    Route::get('/recently-views', 'recentlyViews');
    Route::get('/popular-products', 'popularProducts');
});

// Banner route
Route::controller(BannerController::class)->group(function () {
    Route::get('/banners', 'index');
});

// category route
Route::controller(CategoryController::class)->group(function () {
    Route::get('/categories', 'index');
    Route::get('/category/{category_id}/attributes', 'getCategoryAttributes');
});
Route::get('/child-categories', [ChildCategoryController::class, 'index']);

// category route
Route::controller(SubCategoryController::class)->group(function () {
    Route::get('/sub-categories', 'index');
    Route::get('/sub-categories/{category_id}/attributes', 'getCategoryAttributes');
});


// category products route
Route::get('/category-products', [ProductController::class, 'index']);

// product route
Route::controller(ProductController::class)->group(function () {
    Route::get('/products', 'index');
    Route::get('/product-details', 'show');
});

// review route
Route::controller(ReviewController::class)->group(function () {
    Route::get('/reviews', 'index');
});

// Shops
Route::controller(App\Http\Controllers\Admin\ShopController::class)->group(function () {
    // Route::get('/vendors', 'index')->name('shop.index');
    Route::post('/shop/store', 'store')->name('api.shop.store');
    Route::post('/shop/{shop}/update', 'update')->name('api.shop.update');
    // Route::post('/vendors/store', 'store')->name('shop.store');
});
// shop route
Route::controller(ShopController::class)->group(function () {
    Route::get('/shops', 'index');
    Route::get('/shop-categories', 'shopCategory');
    Route::get('/shops/top', 'topShops');
    Route::get('/shops/popular-products', 'popularProducts');
    Route::get('/shops/{shop}', 'show');
});

// Business Categories
Route::controller(BusinessCategoryController::class)->group(function () {
    Route::get('/business-category', 'index')->name('business-category.index');
    Route::post('/business-category/store', 'store')->name('business-category.store');
    Route::get('/business-category/{businessCategory}/edit', 'edit')->name('business-category.edit');
    Route::put('/business-category/{businessCategory}/update', 'update')->name('business-category.update');
    Route::post('/business-category/{businessCategory}/toggle', 'statusToggle')->name('business-category.toggle');
});

// flash sale route
Route::controller(FlashSaleController::class)->group(function () {
    Route::get('/flash-sales', 'index');
    Route::get('/flash-sale/{flashSale}/details', 'show');
});

// route for blog
Route::controller(BlogController::class)->group(function () {
    Route::get('/blogs', 'index');
    Route::get('/blog/{blog}/details', 'show');
});

Route::get('/get-districts/{stateId}', [LocationController::class, 'getDistricts']);
Route::get('/get-states', [LocationController::class, 'getStates']);

// flash sale route
// Route::controller(LocationController::class)->group(function () {
//     Route::get('/get-districts/{stateId}', 'getDistricts');
//     Route::get('/get-states', 'getStates');
// });

// auth middleware route
Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {

    // user route
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::controller(UserController::class)->group(function () {
        Route::get('/profile', 'index');
        Route::post('/update-profile', 'update')->middleware('demoMode');
        Route::post('/update-single', 'updateSingle')->middleware('demoMode');
        Route::post('/change-password', 'changePassword')->middleware('demoMode');
    });

    // recently view route
    Route::controller(HomeController::class)->group(function () {
        Route::get('/recently-views', 'recentlyViews');
    });


    // followings
    Route::controller(ShopFollowerController::class)->group(function () {
        Route::get('/followings', 'index');
        Route::get('/get-states', 'getStates');
        Route::post('/follow', 'followStore');
    });

    // favorite add or remove route
    Route::controller(ProductController::class)->group(function () {
        Route::post('/favorite-add-or-remove', 'addFavorite');
        Route::get('/favorite-products', 'favoriteProducts');
        Route::post('/product-review', 'storeReview');
    });

    // order route
    Route::controller(OrderController::class)->group(function () {
        Route::get('/orders', 'index');
        Route::post('/place-order', 'store');
        Route::get('/order-details', 'show');
        Route::post('/orders/cancel', 'cancel');
        Route::post('/place-order/again', 'reOrder');
        Route::get('/order-payment/{order}/{paymentMethod?}', 'payment');
    });

    // order route for version 1
    Route::controller(OrderController::class)->prefix('/v1')->group(function () {
        Route::post('/place-order', 'store');
    });

    Route::controller(ReturnOrderController::class)->group(function () {
        Route::get('/return-orders', 'index');
        Route::get('/return-order-details/{returnOrder}', 'show');
        Route::post('/return-order', 'store');
    });

    // coupon route
    Route::controller(CouponController::class)->group(function () {
        Route::get('/get-vouchers', 'index');
        Route::post('/vouchers-collect', 'store')->name('voucher.collect');
        Route::get('/get-collected-vouchers', 'collectedVouchers');
        Route::post('/apply-voucher', 'applyVoucher')->name('voucher.apply');
        Route::post('/coupons/apply', 'getDiscount');
    });

    // address route
    Route::controller(AddressController::class)->group(function () {
        Route::get('/addresses', 'index');
        Route::post('/address/store', 'store');
        Route::post('/address/{address}/update', 'update');
        Route::delete('/address/{address}/delete', 'destroy');
    });

    // cart route
    Route::controller(CartController::class)->group(function () {
        Route::get('/carts', 'index');
        Route::post('/cart/store', 'store');
        Route::post('/cart/increment', 'increment');
        Route::post('/cart/decrement', 'decrement');
        Route::post('/cart/delete', 'destroy');
        Route::post('/cart/checkout', 'checkout');
    });

    // support ticket
    Route::controller(SupportTicketController::class)->group(function () {
        Route::get('/support-tickets', 'index');
        Route::post('/support-ticket', 'store');
        Route::get('/support-ticket/show', 'show');
    });

    // support ticket message
    Route::controller(SupportTicketMessageController::class)->group(function () {
        Route::get('/support-ticket-messages', 'index');
        Route::post('/support-ticket-message', 'store');
    });

    // ticket issue types
    Route::controller(TicketIssueTypeController::class)->group(function () {
        Route::get('ticket-issue-types', 'index');
    });



    // chating with shop and user
    Route::controller(ChatController::class)->group(function () {
        Route::get('/get-shops', 'getShops');
        Route::post('/store-message', 'storeMessage');
        Route::get('/get-message', 'getMessage');
        Route::post('/send-message', 'sendMessage');
    });

    // check user is online or not

    Route::post('/update-last-seen', function () {
        if (!auth()->check()) {
            return;
        }

        $auth = auth()->user();
        $auth->update(['last_online' => now()->addMinute(10)]);

        return response()->noContent();
    });
});


Route::get('/unread-messages', [ChatController::class, 'unreadMessages']);


// language route
Route::get('/lang/{locale}', function ($locale) {
    $path = base_path("lang/{$locale}.json");
    if (File::exists($path)) {
        return File::get($path);
    }

    return response()->json(['error' => 'Language file not found'], 404);
});
Route::post('/incoming-message', [WhatsAppChatController::class, 'incomingMessage']);

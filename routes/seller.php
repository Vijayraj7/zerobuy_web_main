<?php

use App\Http\Controllers\API\Seller\BusinessCategoryController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\Seller\DeliverySettingController;
use App\Http\Controllers\API\Seller\BannerController;
use App\Http\Controllers\API\Seller\AdvertisementController;
use App\Http\Controllers\API\Seller\DashboardController;
use App\Http\Controllers\API\Seller\LoginController;
use App\Http\Controllers\API\Seller\NotificationController;
use App\Http\Controllers\API\Seller\OrderController;
use App\Http\Controllers\API\Seller\ProductController;
use App\Http\Controllers\API\Seller\ReturnOrderController;
use App\Http\Controllers\API\Seller\UserController;
use App\Http\Controllers\API\Seller\WalletController;
use App\Http\Controllers\Seller\SellerChatController;
use App\Http\Controllers\API\Seller\SubscriptionController;
use App\Http\Controllers\Shop\ColorController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Seller\StatusController as SellerStatusController;
use App\Http\Controllers\API\Seller\AnalyticsController as SellerAnalyticsController;

// ==========Route for seller==========
Route::prefix('/seller')->group(function () {

    // auth route
    Route::controller(LoginController::class)->group(function () {
        Route::post('/login', 'login')->name('seller.login');
        // ->middleware('throttle:5,5');
        Route::post('/registration', 'register')->name('seller.register')->middleware('throttle:5,5');
        Route::post('/forgot-password', 'forgotPassword')->middleware('throttle:5,5');
        Route::post('/send-otp', 'sendOTP');
        // ->middleware('throttle:5,5');
        Route::post('/verify-otp', 'verifyOtp');
        Route::get('/check-user-status', 'checkUserStatus');
        Route::post('/check-email-phone', 'checkEmailPhone');
    });

    // auth middleware for rider
    Route::middleware(['auth:sanctum', 'role:shop'])->group(function () {

        Route::get('/filemanager-image', [ProductController::class, 'laravelFilemanagerImage']);
        Route::post('/filemanager/image-upload', [ProductController::class, 'laravelFilemanagerUpload']);

        // user route
        Route::controller(UserController::class)->group(function () {
            Route::get('/details', 'show');
            Route::post('/user-update', 'updateProfile');
            Route::post('/shop-update', 'shopUpdate');
            Route::post('/shop-setting-update', 'shopSettingUpdate');
        });

        // Business Categories
        Route::controller(BusinessCategoryController::class)->group(function () {
            Route::get('/business-category', 'index')->name('business-category.index');
            Route::post('/business-category/shopstore', 'shopstore')->name('business-category.shopstore');
            Route::post('/business-category/store', 'store')->name('business-category.store');
            Route::get('/business-category/{businessCategory}/edit', 'edit')->name('business-category.edit');
            Route::put('/business-category/{businessCategory}/update', 'update')->name('business-category.update');
            Route::post('/business-category/{businessCategory}/toggle', 'statusToggle')->name('business-category.toggle');
        });

        // banner route
        Route::controller(BannerController::class)->group(function () {
            Route::get('/banners', 'index');
            Route::post('/banners/store', 'store');
            Route::post('/banners/update', 'update');
            Route::delete('/banners/{banner}', 'destroy');
        });

        // advertisement route
        Route::controller(AdvertisementController::class)->group(function () {
            Route::get('/advertisements', 'index');
        });

        // advertisement route
        Route::controller(App\Http\Controllers\Shop\AdvertismentController::class)->group(function () {
            Route::post('/advertisements/store', 'store');
        });

        // dashboard route
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // change password
        Route::post('/change-password', [LoginController::class, 'changePassword']);

        // order route
        Route::controller(OrderController::class)->group(function () {
            Route::get('/orders', 'index');
            Route::get('/orders/details', 'show');
            Route::post('/orders/status-update', 'update');
            Route::post('/orders/delivery-charge-update', 'deliveryChargeUpdate');
            Route::post('/orders/track-url-update', 'trackUrlUpdate');
        });

        Route::controller(ReturnOrderController::class)->group(function () {
            Route::get('/return-orders', 'index');
            Route::get('/return/{returnOrder}/show', 'show');
            Route::post('/return/update-status', 'statusChange');
        });

        Route::controller(DeliverySettingController::class)->group(function () {
            Route::get('/delivery-settings', 'show');
            Route::get('/get-states', 'getStates');
            Route::post('/delivery-settings/selected-states', 'saveSelectedStates');
            Route::post('/delivery-settings', 'store');
        });


        Route::controller(ColorController::class)->group(function () {
            Route::get('/get-color', 'getcolors');
            Route::post('/save-color', 'saveColorsAndSizes');
            // Route::get('/get-states', 'getStates');
            // Route::post('/delivery-settings/selected-states',  'saveSelectedStates');
            // Route::post('/delivery-settings', 'store');
        });

        // wallet route
        Route::controller(WalletController::class)->group(function () {
            Route::get('/wallet', 'index');
            Route::get('/wallet/history', 'history');
            Route::post('/wallet/withdraw', 'withdraw');
            Route::get('/wallet/transactions', 'transactions');
        });


        // Subscription
        Route::controller(SubscriptionController::class)->group(function () {
            Route::get('/subscription', 'index');
            Route::get('/subscription/purchase', 'purchase');
            Route::post('/subscription/purchase', 'purchase');
        });


        // notification
        Route::controller(NotificationController::class)->group(function () {
            Route::get('/notifications', 'index');
            Route::post('/notifications/{notification}', 'update');
            Route::delete('/notifications/{notification}', 'delete');
        });

        // Products
        Route::controller(ProductController::class)->group(function () {
            Route::get('/products', 'index');
            Route::post('/product/store', 'store');
            Route::post('/product/{product}/update', 'update');
            Route::get('/product/{product}/show', 'show');
            Route::get('/product/create-data', 'createData');
            Route::post('/product/status/toggle/{product}', 'statusToggle');
            Route::delete('/product/{product}/destroy', 'destroy');
            Route::delete('/product/thumbnail/delete', 'thumbnailDestroy');
        });

        // customer messages route
        Route::controller(SellerChatController::class)->group(function () {
            Route::get('/get-users', 'getUsers');
            Route::get('/get-message', 'getMessageAdmin');
            Route::post('/send-message', 'sendMessageAdmin');
            Route::get('/unread-messages', 'unreadMessages');
        });

        // seller product statuses
        Route::get('/statuses', [SellerStatusController::class, 'index']);
        Route::post('/statuses', [SellerStatusController::class, 'store']);
        Route::delete('/statuses/{statusId}', [SellerStatusController::class, 'destroy']);

        // seller analytics
        Route::get('/analytics/summary', [SellerAnalyticsController::class, 'summary']);
        Route::get('/analytics/top-products', [SellerAnalyticsController::class, 'topProducts']);
        Route::get('/analytics/top-customers', [SellerAnalyticsController::class, 'topCustomers']);


        // logout
        Route::get('/logout', [LoginController::class, 'logout']);
        Route::delete('/delete-account', [LoginController::class, 'deleteAccountSeller']);
    });
});

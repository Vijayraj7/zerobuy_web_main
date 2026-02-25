<?php

namespace App\Http\Controllers\API;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\ProductStatus;
use Illuminate\Http\Request;

class DashboardCountController extends Controller
{
    private function parseViewedStatusIds($raw)
    {
        $normalize = function ($value) {
            if (is_string($value) && str_contains($value, '_')) {
                $parts = explode('_', $value);
                $value = end($parts);
            }

            return (int) $value;
        };

        if (is_array($raw)) {
            return array_values(array_filter(array_map($normalize, $raw)));
        }

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map($normalize, $decoded)));
            }

            return array_values(array_filter(array_map($normalize, explode(',', $raw))));
        }

        return [];
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $customer = $user?->customer;

        if (! $user || ! $customer) {
            return $this->json('Dashboard counts', [
                'status_shops_count' => 0,
                'status_items_count' => 0,
                'notification_unread_count' => 0,
                'pending_orders_count' => 0,
                'cart_items_count' => 0,
                'favorites_count' => 0,
            ]);
        }

        $followedShopIds = $customer->followedShops()->pluck('shops.id');
        $viewedStatusIds = $this->parseViewedStatusIds($request->input('viewed_status_ids'));

        $activeStatuses = ProductStatus::active()->whereIn('shop_id', $followedShopIds);

        if (! empty($viewedStatusIds)) {
            $activeStatuses = $activeStatuses->whereNotIn('id', $viewedStatusIds);
        }

        $statusItemsCount = (int) $activeStatuses->count();
        $statusShopsCount = (int) $activeStatuses->distinct('shop_id')->count('shop_id');

        $notificationUnreadCount = (int) Notification::where('user_id', $user->id)
            ->where('is_read', 0)
            ->count();

        $pendingOrdersCount = (int) $customer->orders()
            ->where('order_status', OrderStatus::PENDING->value)
            ->count();

        $cartItemsCount = (int) $customer->carts()
            ->sum('quantity');

        // Fetch count of favorites (wishlist) for the customer
        $favoritesCount = 0;
        if (method_exists($customer, 'favorites')) {
            $favoritesCount = (int) $customer->favorites()->count();
        } elseif (method_exists($customer, 'wishlists')) {
            $favoritesCount = (int) $customer->wishlists()->count();
        }

        return $this->json('Dashboard counts', [
            'status_shops_count' => $statusShopsCount,
            'status_items_count' => $statusItemsCount,
            'notification_unread_count' => $notificationUnreadCount,
            'pending_orders_count' => $pendingOrdersCount,
            'cart_items_count' => $cartItemsCount,
            'favorites_count' => $favoritesCount,
        ]);
    }
}

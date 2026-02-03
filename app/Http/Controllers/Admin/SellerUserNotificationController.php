<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BusinessCategory;
use App\Models\SubCategory;
use App\Models\ChildCategory;
use App\Models\Product;
use App\Models\Shop;
use App\Models\PromotionalNotification;
use App\Repositories\MediaRepository;
use App\Services\NotificationServices;

class SellerUserNotificationController extends Controller
{
    private function sendToAllUsers($noti)
    {
        $topic = 'topic_customers';
        if ($noti->business_category_id) {
            $topic = 'topic_businesscategory_' . $noti->business_category_id;
        }
        NotificationServices::sendNotificationToTopic(
            '',
            $topic,
            $noti->message,
            [
                'id' => (string) $noti->id,
                'notification_option_type' => $noti->notification_option_type,
                'notification_option_link' => (string) $noti->notification_option_link,
                'image' => $noti->media->src_url ?? '',
                'business_category_id' => (string) $noti->business_category_id,
            ],
            $noti->media->src_url ?? null
        );
        return true;
    }

    private function sendToSeller($shopId, $noti)
    {
        NotificationServices::sendNotificationToTopic(
            '',
            $shopId == null ? 'topic_sellers' :   'topic_sellers_' . $shopId,
            $noti->message,
            [
                'id' => (string) $noti->id,
                'notification_option_type' => $noti->notification_option_type,
                'notification_option_link' => (string) $noti->notification_option_link,
                'image' => $noti->media->src_url ?? '',
                'business_category_id' => (string) $noti->business_category_id,
            ],
            $noti->media->src_url ?? null
        );
        return true;
    }

    public function index()
    {
        $businessCategories = BusinessCategory::where('status', 1)->get();

        $userNotifications = PromotionalNotification::where('send_to', 'user')->latest()->get();
        $sellerNotifications = PromotionalNotification::where('send_to', 'seller')->latest()->get();

        return view('admin.notification.user-seller-index', compact(
            'businessCategories',
            'userNotifications',
            'sellerNotifications'
        ));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'business_category_id' => 'required',
            'notification_option_type' => 'nullable',
            'notification_option_link' => 'nullable',
            'notification_banner' => 'required|image',
            'message' => 'nullable|string',
        ]);

        $thumbnail = MediaRepository::storeByRequest($request->notification_banner, 'banners', 'thumbnail', 'image');

        $productId = null;
        $storeId = null;

        if ($request->notification_option_type === 'product') {
            $productId = $request->notification_option_link;
        }

        if ($request->notification_option_type === 'shop') {
            $storeId = $request->notification_option_link;
        }

        $noti = PromotionalNotification::create([
            'send_to' => 'user',
            'business_category_id' => $request->business_category_id,
            'notification_option_type' => $request->notification_option_type,
            'notification_option_link' => $request->notification_option_link,
            'media_id' => $thumbnail->id,
            'message' => $request->message,
            'last_sent_at' => now(),
        ]);

        // send now (users)
        $this->sendToAllUsers($noti);

        return back()->with('success', 'User notification created & sent!');
    }

    public function updateUser(Request $request, $id)
    {
        $request->validate([
            'business_category_id' => 'required',
            'notification_option_type' => 'nullable',
            'notification_option_link' => 'nullable',
            'notification_banner' => 'nullable|image',
            'message' => 'nullable|string',
        ]);

        $noti = PromotionalNotification::findOrFail($id);

        if ($request->hasFile('notification_banner')) {
            $thumbnail = MediaRepository::storeByRequest($request->notification_banner, 'banners', 'thumbnail', 'image');
            $noti->media_id = $thumbnail->id;
        }

        $noti->update([
            'business_category_id' => $request->business_category_id,
            'notification_option_type' => $request->notification_option_type,
            'notification_option_link' => $request->notification_option_link,
            'message' => $request->message,
        ]);

        return back()->with('success', 'User notification updated successfully!');
    }

    public function storeSeller(Request $request)
    {
        $request->validate([
            'seller_business_category_id' => 'required',
            'shop_id' => 'nullable',
            'notification_banner' => 'required|image',
            'message' => 'nullable|string',
        ]);
        $thumbnail = MediaRepository::storeByRequest($request->notification_banner, 'banners', 'thumbnail', 'image');

        $noti = PromotionalNotification::create([
            'send_to' => 'seller',
            'business_category_id' => $request->seller_business_category_id,
            'shop_id' => $request->shop_id,
            'media_id' => $thumbnail->id,
            'message' => $request->message,
            'last_sent_at' => now(),
        ]);

        // send now (seller)
        $this->sendToSeller($noti->shop_id, $noti);

        return back()->with('success', 'Seller notification created & sent!');
    }

    public function updateSeller(Request $request, $id)
    {
        $request->validate([
            'seller_business_category_id' => 'required',
            'shop_id' => 'nullable',
            'notification_banner' => 'nullable|image',
            'message' => 'nullable|string',
        ]);

        $noti = PromotionalNotification::findOrFail($id);

        if ($request->hasFile('notification_banner')) {
            $thumbnail = MediaRepository::storeByRequest($request->notification_banner, 'banners', 'thumbnail', 'image');
            $noti->media_id = $thumbnail->id;
        }

        $noti->update([
            'business_category_id' => $request->seller_business_category_id,
            'shop_id' => $request->shop_id,
            'message' => $request->message,
        ]);

        return back()->with('success', 'Seller notification updated successfully!');
    }

    public function optionTypes(Request $request)
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
                ->when(
                    $search,
                    fn($q) =>
                    $q->where('name', 'like', "%{$search}%")
                )
                ->select('id', 'name')
                ->get(),

            /* ================= CHILD CATEGORY ================= */
            'child_category' =>
            ChildCategory::where('business_category_id', $businessCategoryId)
                ->when(
                    $search,
                    fn($q) =>
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
                ->map(fn($p) => [
                    'id'   => $p->id,
                    'name' => 'PRD0' . $p->id . ' - ' . $p->name,
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
                ->map(fn($s) => [
                    'id'   => $s->id,
                    'name' => 'STR0' . $s->id . ' - ' . $s->name,
                ]),

            default => [],
        };
    }

    public function sellerShops(Request $request)
    {
        $search = $request->search;

        return Shop::when(
            $search,
            fn($q) =>
            $q->where('name', 'like', "%{$search}%")
        )
            ->select('id', 'name')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'name' => 'STR0' . $s->id . ' - ' . $s->name
            ]);
    }

    public function resend($id)
    {
        $noti = PromotionalNotification::findOrFail($id);

        if ($noti->send_to === 'user') {
            $this->sendToAllUsers($noti);
        } else {
            $this->sendToSeller($noti->shop_id, $noti);
        }

        $noti->update(['last_sent_at' => now()]);

        return back()->with('success', 'Notification sent again!');
    }

    public function delete($id)
    {
        PromotionalNotification::findOrFail($id)->delete();
        return back()->with('success', 'Notification deleted!');
    }
}

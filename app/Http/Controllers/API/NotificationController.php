<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            $notifications = Notification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => (int)$notification->id,
                        'title' => $notification->title ?? '',
                        'content' => $notification->content ?? '',
                        'url' => $notification->url,
                        'icon' => $notification->icon,
                        'type' => $notification->type ?? '',
                        'shop_id' => $notification->shop_id !== null ? (int)$notification->shop_id : null,
                        'user_id' => $notification->user_id,
                        'is_read' => $notification->is_read ? 1 : 0,
                        'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $notification->updated_at->format('Y-m-d H:i:s'),
                        'withdraw_id' => $notification->withdraw_id !== null ? (int)$notification->withdraw_id : null,
                    ];
                });

            return $this->json('notifications', [
                'notifications' => $notifications,
            ]);
        } catch (\Exception $e) {
            return $this->json('failed to fetch notifications', [
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        try {
            $user = Auth::user();

            $notification = Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $notification->update(['is_read' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $user = Auth::user();

            Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete all notifications
     */
    public function clearAll(Request $request)
    {
        try {
            $user = Auth::user();

            Notification::where('user_id', $user->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'All notifications cleared',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear notifications',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

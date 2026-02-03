<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;

class NotificationServices
{
    public static function sendNotification(string $body, array $tokens, $title = null)
    {
        $notification = Notification::create($title, $body);

        $firebaseCredentials = storage_path('app/public/firebase/firebase_credentials.json');

        if (! file_exists($firebaseCredentials)) {
            return [
                'success' => false,
                'message' => 'Firebase notification can not be sent. Please provide valid firebase credentials file.',
            ];
        }

        $messaging = (new Factory)->withServiceAccount($firebaseCredentials)->createMessaging();

        $androidConfig = AndroidConfig::fromArray([
            'notification' => [
                'icon' => 'notification_icon',
                // 'channel_id' => 'high_importance_channel',
                'sound' => 'default',
            ],
        ]);

        $message = CloudMessage::new()
            ->withNotification($notification);
        // ->withAndroidConfig($androidConfig);

        try {
            $messaging->sendMulticast($message, $tokens);

            return [
                'success' => true,
                'message' => 'Notification sent successfully',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public static function sendNotificationToTopic(string $body, string $topic, $title = null, array $data = [], $imageUrl = null)
    {
        $notification = Notification::create($title, $body);
        
        if ($imageUrl) {
            $notification = Notification::create($title, $body, $imageUrl);
        }

        $firebaseCredentials = storage_path('app/public/firebase/firebase_credentials.json');

        if (! file_exists($firebaseCredentials)) {
            return [
                'success' => false,
                'message' => 'Firebase notification can not be sent. Please provide valid firebase credentials file.',
            ];
        }

        $messaging = (new Factory)->withServiceAccount($firebaseCredentials)->createMessaging();

        $androidConfig = AndroidConfig::fromArray([
            'notification' => [
                'icon' => 'notification_icon',
                'color' => '#00846F',
                'channel_id' => 'high_importance_channel',
                'sound' => 'default',
                'image' => $imageUrl,
            ],
        ]);

        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification($notification)
            ->withAndroidConfig($androidConfig);

        if (!empty($data)) {
            $message = $message->withData($data);
        }

        try {
            $messaging->send($message);

            return [
                'success' => true,
                'message' => 'Notification sent to topic successfully',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}

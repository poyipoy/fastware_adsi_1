<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Send FCM Notification (HTTP v1)
     * 
     * @param string $token Device token
     * @param string $title Notification Title
     * @param string $body Notification Body
     * @param array $data Optional data payload
     * @return bool
     */
    public static function sendNotification($token, $title, $body, $data = [])
    {
        // PATH KE FILE JSON SERVICE ACCOUNT
        // Pastikan nama file sesuai dengan yang Anda upload ke storage
        $serviceAccountPath = storage_path('app/fastware-android-firebase-adminsdk-fbsvc-46a2fc1217.json');

        try {
            if (!file_exists($serviceAccountPath)) {
                Log::error("FCM Service Account file not found at: " . $serviceAccountPath);
                return false;
            }

            $factory = (new \Kreait\Firebase\Factory)
                ->withServiceAccount($serviceAccountPath);

            $messaging = $factory->createMessaging();

            // Ubah semua nilai data menjadi string karena FCM hanya support string di 'data' field
            $stringData = array_map(function($value) {
                 return (string) $value;
            }, $data);

            $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $token)
                ->withNotification(\Kreait\Firebase\Messaging\Notification::create($title, $body))
                ->withData($stringData);

            $messaging->send($message);
            
            Log::info('FCM (HTTP v1) sent successfully to: ' . $token);
            return true;

        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            Log::error('FCM Messaging Error: ' . $e->getMessage());
            return false;
        } catch (\Kreait\Firebase\Exception\FirebaseException $e) {
            Log::error('FCM Firebase Error: ' . $e->getMessage());
            return false;
        } catch (\Throwable $e) {
            Log::error('FCM General Error: ' . $e->getMessage());
            return false;
        }
    }
}

<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\FirebaseException;
use Illuminate\Support\Facades\Log;



class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $credentialsPath = base_path(env('FIREBASE_CREDENTIALS'));

        if (!file_exists($credentialsPath)) {
            throw new \Exception("Firebase credentials file not found at: {$credentialsPath}");
        }

        $factory = (new Factory)->withServiceAccount($credentialsPath);

        $this->messaging = $factory->createMessaging();
    }

    /**
     * Send notification to multiple FCM tokens
     */
    public function sendNotification(array $tokens, string $title, string $body)
    {
        if (empty($tokens)) {
            Log::warning('No FCM tokens provided.');
            return;
        }

        $notification = Notification::create($title, $body);

        foreach ($tokens as $token) {
            try {
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification($notification);

                $this->messaging->send($message);

                Log::info("Notification sent to token: {$token}");
            } catch (FirebaseException $e) {
                Log::error("Firebase exception: {$e->getMessage()} for token: {$token}");
            } catch (\Exception $e) {
                Log::error("General exception: {$e->getMessage()} for token: {$token}");
            }
        }
    }
}

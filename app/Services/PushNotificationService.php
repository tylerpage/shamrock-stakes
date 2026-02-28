<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\VAPID;

class PushNotificationService
{
    /** @var WebPush|null */
    protected $webPush;

    public function __construct()
    {
        $subject = config('services.vapid.subject');
        $publicKey = config('services.vapid.public_key');
        $privateKey = config('services.vapid.private_key');

        if (!$subject || !$publicKey || !$privateKey) {
            $this->webPush = null;
            return;
        }

        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ], [
            'TTL' => 86400, // 1 day
            'urgency' => 'normal',
        ]);
    }

    /**
     * Send a push notification to a single subscription.
     * Returns true if sent successfully, false if not configured or send failed.
     */
    public function send(PushSubscription $model, string $title, string $body = '', array $data = []): bool
    {
        if (!$this->webPush || !$model->public_key || !$model->auth_token) {
            return false;
        }

        $payload = json_encode(array_merge(
            ['title' => $title, 'body' => $body],
            $data
        ));

        $subscription = Subscription::create([
            'endpoint' => $model->endpoint,
            'keys' => [
                'p256dh' => $model->public_key,
                'auth' => $model->auth_token,
            ],
        ]);

        try {
            $report = $this->webPush->sendOneNotification($subscription, $payload, [
                'contentType' => 'application/json',
            ]);
            if (!$report->isSuccess()) {
                // Subscription may be expired; caller could delete it
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    /**
     * Send the same notification to all subscriptions for the given user IDs.
     * Skips users with no valid subscription.
     */
    public function sendToUsers(array $userIds, string $title, string $body = '', array $data = []): void
    {
        if (!$this->webPush || empty($userIds)) {
            return;
        }

        $subscriptions = PushSubscription::whereIn('user_id', $userIds)
            ->whereNotNull('public_key')
            ->whereNotNull('auth_token')
            ->get();

        foreach ($subscriptions as $sub) {
            $this->send($sub, $title, $body, $data);
        }
    }

    /**
     * Check if push is configured (VAPID keys set).
     */
    public function isConfigured(): bool
    {
        return $this->webPush !== null;
    }

    /**
     * Generate VAPID keys (for artisan command).
     */
    public static function generateVapidKeys(): array
    {
        return VAPID::createVapidKeys();
    }
}

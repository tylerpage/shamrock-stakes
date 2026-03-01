<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;

class SendTestPushCommand extends Command
{
    protected $signature = 'push:test
                            {user? : User ID or email to send the test notification to (default: first user with a subscription)}
                            {--title= : Notification title}
                            {--body= : Notification body}';

    protected $description = 'Send a test push notification to a user (for testing Web Push setup).';

    public function handle(PushNotificationService $push): int
    {
        if (!$push->isConfigured()) {
            $this->error('Push is not configured. Set VAPID_SUBJECT, VAPID_PUBLIC_KEY, and VAPID_PRIVATE_KEY in .env (run php artisan web-push:vapid to generate keys).');
            return 1;
        }

        $user = $this->resolveUser();
        if (!$user) {
            $this->error('No user found. Specify a user ID or email, or ensure at least one user has enabled push (click "Enable push notifications" in the app).');
            return 1;
        }

        $subCount = $user->pushSubscriptions()->whereNotNull('public_key')->whereNotNull('auth_token')->count();
        if ($subCount === 0) {
            $this->error("User {$user->email} has no push subscription. Have them log in, click their name → 'Enable push notifications', and allow the browser prompt.");
            return 1;
        }

        $title = $this->option('title') ?: 'Test from Shamrock Stakes';
        $body = $this->option('body') ?: 'If you see this, push notifications are working.';

        $push->sendToUsers([$user->id], $title, $body, ['url' => url('/parties')]);
        $this->info("Test notification sent to {$user->email} ({$subCount} subscription(s)).");
        return 0;
    }

    private function resolveUser(): ?User
    {
        $userArg = $this->argument('user');
        if ($userArg) {
            if (is_numeric($userArg)) {
                return User::find($userArg);
            }
            return User::where('email', $userArg)->first();
        }
        return User::whereHas('pushSubscriptions')->first();
    }
}

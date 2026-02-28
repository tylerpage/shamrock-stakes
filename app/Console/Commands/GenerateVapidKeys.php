<?php

namespace App\Console\Commands;

use App\Services\PushNotificationService;
use Illuminate\Console\Command;

class GenerateVapidKeys extends Command
{
    protected $signature = 'web-push:vapid';
    protected $description = 'Generate VAPID keys for Web Push notifications. Add the output to your .env file.';

    public function handle(): int
    {
        $keys = PushNotificationService::generateVapidKeys();
        $this->line('Add these to your .env file (subject must be mailto: or an https URL):');
        $this->newLine();
        $this->line('VAPID_SUBJECT=mailto:your@email.com');
        $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
        $this->newLine();
        $this->info('Also expose VAPID_PUBLIC_KEY to the front-end (see config/services.php vapid.public_key) so the browser can subscribe with it.');
        return 0;
    }
}

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Soketi / WebSocket (for Echo when behind ngrok or public URL)
    |--------------------------------------------------------------------------
    | Set SOKETI_PUBLIC_URL to your Soketi server's public URL (e.g. from a
    | second ngrok tunnel for port 6001) so the browser connects there instead
    | of 127.0.0.1. Example: https://your-soketi.ngrok-free.app
    */
    'soketi' => [
        'public_url' => env('SOKETI_PUBLIC_URL', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | VAPID (Web Push)
    |--------------------------------------------------------------------------
    | Required to send push notifications. Generate with:
    | php artisan web-push:vapid
    | or: openssl ecparam -genkey -name prime256v1 -out private_key.pem
    | then use the library's VAPID::createVapidKeys() or the artisan command.
    | Subject must be mailto: or an https URL (browsers require it).
    */
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@example.com'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

];

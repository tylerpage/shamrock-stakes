<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Store or update a push subscription for the current user (PWA). */
    public function store(Request $request)
    {
        $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required_with:keys', 'string'],
            'keys.auth' => ['required_with:keys', 'string'],
        ]);
        PushSubscription::updateOrCreate(
            ['endpoint' => $request->endpoint],
            [
                'user_id' => auth()->id(),
                'public_key' => $request->keys['p256dh'] ?? null,
                'auth_token' => $request->keys['auth'] ?? null,
                'user_agent' => $request->userAgent(),
            ]
        );
        return response()->json(['status' => 'ok']);
    }
}

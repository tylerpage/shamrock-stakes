<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $existingUser ? "You're in: " . $party->name : "You're invited: " . $party->name }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 560px; margin: 0 auto; padding: 24px; }
        h1 { font-size: 1.25rem; margin-bottom: 16px; }
        .party-name { font-weight: 600; }
        a.btn { display: inline-block; margin-top: 16px; padding: 12px 20px; background: #0d6efd; color: #fff !important; text-decoration: none; border-radius: 6px; }
        a.btn:hover { background: #0b5ed7; }
        p { margin: 0 0 12px; }
        .muted { color: #6c757d; font-size: 0.9rem; }
    </style>
</head>
<body>
    <h1>
        @if($existingUser)
            You're in: <span class="party-name">{{ $party->name }}</span>
        @else
            You're invited: <span class="party-name">{{ $party->name }}</span>
        @endif
    </h1>

    @if($existingUser)
        <p>You've been added to the party <strong>{{ $party->name }}</strong>.</p>
        <p>Log in and open <strong>My Parties</strong> to join and start playing.</p>
        <a href="{{ url('/parties') }}" class="btn">Open My Parties</a>
    @else
        <p>You've been invited to the party <strong>{{ $party->name }}</strong>.</p>
        <p>Register with this email address to join. Once you're in, you'll see the party under <strong>My Parties</strong>.</p>
        <a href="{{ url('/register') }}" class="btn">Register to join</a>
    @endif

    <p class="muted" style="margin-top: 24px;">This is a prediction market party. If you didn't expect this invite, you can ignore this email.</p>
</body>
</html>

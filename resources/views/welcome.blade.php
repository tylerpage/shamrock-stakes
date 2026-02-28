<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shamrock Stakes — Prediction markets for fun</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; background: #f5f0e6; margin: 0; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #0d3328; }
        .hero { text-align: center; padding: 2rem; }
        .hero h1 { font-family: 'Bebas Neue', sans-serif; font-size: 3.5rem; color: #0d3328; letter-spacing: 0.05em; margin-bottom: 0.5rem; }
        .hero .tagline { font-size: 1.2rem; color: #1a4d3e; margin-bottom: 2rem; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 700; transition: all 0.2s; }
        .btn-primary { background: #0d3328; color: #e8c547; border: none; }
        .btn-primary:hover { background: #1a4d3e; color: #fff; }
        .btn-secondary { background: transparent; color: #0d3328; border: 2px solid #0d3328; margin-left: 0.5rem; }
        .btn-secondary:hover { background: #0d3328; color: #e8c547; }
    </style>
</head>
<body>
    <div class="hero">
        <h1>☘ Shamrock Stakes</h1>
        <p class="tagline">Prediction markets for your party. Pre-vote, bet fake cash, settle by vote or official outcome.</p>
        @if (Route::has('login'))
            @auth
                <a href="{{ url('/parties') }}" class="btn btn-primary">Go to my parties</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary">Log in</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-secondary">Register</a>
                @endif
            @endauth
        @endif
    </div>
</body>
</html>

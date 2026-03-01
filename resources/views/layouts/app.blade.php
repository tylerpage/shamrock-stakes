<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0d3328">
    <link rel="manifest" href="{{ url('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ url('icons/icon-180.png') }}">
    <link rel="apple-touch-icon" sizes="120x120" href="{{ url('icons/icon-120.png') }}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{ url('icons/icon-152.png') }}">
    <link rel="apple-touch-icon" sizes="167x167" href="{{ url('icons/icon-167.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url('icons/icon-180.png') }}">
    <title>@yield('title', 'Shamrock Stakes') — {{ config('app.name', 'Shamrock Stakes') }}</title>
    @php
        // Echo config so the browser connects to the right WebSocket (not 127.0.0.1 when deployed).
        // Priority: SOKETI_PUBLIC_URL (e.g. ngrok) → REVERB_* (Laravel Cloud) → PUSHER_* (Soketi/local).
        $echoConfig = null;
        $soketiUrl = rtrim(config('services.soketi.public_url', ''), '/');
        if ($soketiUrl !== '') {
            $parsed = parse_url($soketiUrl);
            $host = $parsed['host'] ?? null;
            if (!empty($host)) {
                $useTls = ($parsed['scheme'] ?? '') === 'https';
                $port = isset($parsed['port']) ? (int) $parsed['port'] : ($useTls ? 443 : 80);
                $echoConfig = [
                    'wsHost' => $host,
                    'wsPort' => $port,
                    'wssPort' => $port,
                    'forceTLS' => $useTls,
                    'key' => config('broadcasting.connections.pusher.key', 'app-key'),
                    'cluster' => config('broadcasting.connections.pusher.options.cluster', 'mt1'),
                ];
            }
        }
        if ($echoConfig === null && env('REVERB_APP_KEY')) {
            $echoConfig = [
                'wsHost' => env('REVERB_HOST'),
                'wsPort' => (int) env('REVERB_PORT', 443),
                'wssPort' => (int) env('REVERB_PORT', 443),
                'forceTLS' => env('REVERB_SCHEME', 'https') === 'https',
                'key' => env('REVERB_APP_KEY'),
                'cluster' => 'mt1',
            ];
        }
        if ($echoConfig === null && config('broadcasting.connections.pusher.key')) {
            $echoConfig = [
                'wsHost' => config('broadcasting.connections.pusher.options.host'),
                'wsPort' => (int) config('broadcasting.connections.pusher.options.port'),
                'wssPort' => (int) config('broadcasting.connections.pusher.options.port'),
                'forceTLS' => config('broadcasting.connections.pusher.options.useTLS'),
                'key' => config('broadcasting.connections.pusher.key'),
                'cluster' => config('broadcasting.connections.pusher.options.cluster', 'mt1'),
            ];
        }
    @endphp
    @if(!empty($echoConfig))
    <script>
        window.SHAMROCK_ECHO_CONFIG = @json($echoConfig);
    </script>
    @endif
    @php
        $appJsPath = public_path('js/app.js');
        $appCssPath = public_path('css/app.css');
        $assetVersion = config('app.asset_version') ?: (is_file($appJsPath) ? filemtime($appJsPath) : (is_file($appCssPath) ? filemtime($appCssPath) : '1'));
    @endphp
    <script src="{{ asset('js/app.js') }}?v={{ $assetVersion }}" defer></script>
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v={{ $assetVersion }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="shamrock-body">
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-dark shamrock-nav shadow">
            <div class="container">
                <a class="navbar-brand shamrock-brand" href="{{ url('/') }}">☘ Shamrock Stakes</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto">
                        @auth
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('parties.index') }}">My Parties</a>
                            </li>
                            @if(Auth::user()->is_admin)
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.parties.index') }}">Parties</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.users.index') }}">Users</a>
                                </li>
                            @endif
                        @endauth
                    </ul>
                    <ul class="navbar-nav ms-auto">
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Login</a></li>
                            @endif
                            @if (Route::has('register'))
                                <li class="nav-item"><a class="nav-link" href="{{ route('register') }}">Register</a></li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }}
                                </a>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="#" id="enable-push">Enable push notifications</a>
                                    <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>
        <main class="py-4">
            @if (session('success'))
                <div class="container"><div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div></div>
            @endif
            @yield('content')
        </main>
    </div>
    @auth
    <script>
      if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('{{ asset("sw.js") }}').then(function () {});
      }
      document.getElementById('enable-push') && document.getElementById('enable-push').addEventListener('click', function (e) {
        e.preventDefault();
        if (!('Notification' in window) || !('PushManager' in window)) { alert('Push not supported'); return; }
        Notification.requestPermission().then(function (perm) {
          if (perm !== 'granted') return;
          navigator.serviceWorker.ready.then(function (reg) {
            var opts = { userVisibleOnly: true };
            var vapidKey = '{{ config("services.vapid.public_key") }}';
            if (vapidKey) {
              opts.applicationServerKey = urlBase64ToUint8Array(vapidKey);
            } else {
              opts.applicationServerKey = null;
            }
            reg.pushManager.subscribe(opts).then(function (sub) {
              var j = sub.toJSON();
              fetch('{{ route("push.subscribe") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: JSON.stringify({ endpoint: j.endpoint, keys: { p256dh: j.keys && j.keys.p256dh, auth: j.keys && j.keys.auth } })
              }).then(function () { alert('Notifications enabled'); });
            });
          });
        });
      });
      function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var rawData = window.atob(base64);
        var outputArray = new Uint8Array(rawData.length);
        for (var i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
        return outputArray;
      }
    </script>
    @endauth
    @stack('scripts')
</body>
</html>

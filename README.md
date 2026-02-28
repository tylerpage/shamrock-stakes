# Shamrock Stakes ☘

Prediction market app for parties: create parties, invite users, grant fake dollars, add yes/no or people-type markets with optional images. Users pre-vote to set initial odds, then bet when markets go live. Resolve by official outcome or by party vote (24h after end). Leaderboard and PWA with push support.

## Stack

- **Laravel 8** + **Laravel UI** (Bootstrap auth)
- **PWA**: manifest, service worker, push subscription storage
- **Real-time odds**: `GET /api/markets/{market}/odds` (poll or use with Laravel Echo); `MarketOddsUpdated` event broadcast on channel `market.{id}` when a bet or pre-vote is added

## Setup

1. **Install dependencies**
   ```bash
   composer install
   npm install && npm run dev
   ```

2. **Environment**
   - Copy `.env.example` to `.env`, run `php artisan key:generate`
   - Set `APP_NAME=Shamrock Stakes` (optional)
   - Configure DB (e.g. SQLite or MySQL)

3. **Database**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```
   First admin: set in `.env` (optional):
   - `ADMIN_EMAIL=admin@example.com`
   - `ADMIN_NAME=Admin`
   - `ADMIN_PASSWORD=password`
   Or create a user and set `is_admin = 1` in the database.

4. **Storage link** (for market option images)
   ```bash
   php artisan storage:link
   ```

5. **PWA icons** (optional)  
   Add `public/icons/icon-192.png` and `public/icons/icon-512.png` for install icons. The app works without them.

## Run WebSockets locally (real-time odds)

To push `MarketOddsUpdated` over WebSockets so the UI can update odds without polling, run a Pusher-compatible server locally with **Soketi**.

1. **Install the Pusher PHP SDK** (Laravel uses it to send events to the WebSocket server):
   ```bash
   composer require pusher/pusher-php-server
   ```

2. **Start Soketi** (in a separate terminal). Soketi 1.6.x only supports **Node 14, 16 or 18** (not Node 20+). Use one of these:

   - **Docker** (easiest if you have Docker; ignores your system Node version):
     ```bash
     docker run -p 6001:6001 -p 9601:9601 quay.io/soketi/soketi:1.4-16-debian
     ```
   - **Node 18 via npx** (if you use [nvm](https://github.com/nvm-sh/nvm)):
     ```bash
     nvm install 18
     nvm use 18
     npx @soketi/soketi start
     ```
   - **Global install** (with Node 18 active):
     ```bash
     npm install -g @soketi/soketi
     soketi start
     ```

   Soketi listens on **6001** (WebSocket) and **9601** (HTTP API). Default app: `app-id`, `app-key`, `app-secret`.

3. **Configure Laravel** in `.env`:
   ```env
   BROADCAST_DRIVER=pusher
   QUEUE_CONNECTION=sync
   PUSHER_APP_ID=app-id
   PUSHER_APP_KEY=app-key
   PUSHER_APP_SECRET=app-secret
   PUSHER_APP_CLUSTER=mt1
   PUSHER_HOST=127.0.0.1
   PUSHER_PORT=6001
   PUSHER_SCHEME=http
   MIX_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
   MIX_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
   MIX_PUSHER_HOST="${PUSHER_HOST}"
   MIX_PUSHER_PORT="${PUSHER_PORT}"
   ```
   The `MIX_*` vars are used by the frontend (Laravel Echo) to connect to Soketi so the party page can show live odds. Use `QUEUE_CONNECTION=sync` for testing (no worker); use `database` and `php artisan queue:work` for production if you prefer.

4. **Run the queue worker** (if you set `QUEUE_CONNECTION=database`):
   ```bash
   php artisan queue:work
   ```

5. **Optional – listen in the browser**: Install Laravel Echo and the Pusher JS client, then subscribe to `market.{id}` and update the odds in the DOM when `MarketOddsUpdated` is received. Without Echo, events still flow to Soketi; the app already supports polling `GET /api/markets/{id}/odds` for updates.

### WebSockets not working? Checklist

- **`BROADCAST_DRIVER=pusher`** in `.env`. If it’s `null` or `log`, Laravel won’t send events to Soketi.
- **Soketi is running** (Docker or `npx @soketi/soketi start` with Node 18). Check that something is listening on port **6001**.
- **PUSHER_* match Soketi’s default app**: `PUSHER_APP_ID=app-id`, `PUSHER_APP_KEY=app-key`, `PUSHER_APP_SECRET=app-secret`, `PUSHER_HOST=127.0.0.1`, `PUSHER_PORT=6001`, `PUSHER_SCHEME=http`.
- **Queue**: With `QUEUE_CONNECTION=sync`, broadcasts run immediately (no worker needed). With `database` or `redis`, run `php artisan queue:work` or events never get sent.
- **Seeing events in the app**: The frontend now subscribes via Echo. If you still don’t see live updates:
  1. Add **`?echo_debug=1`** to the party page URL (e.g. `/parties/1?echo_debug=1`), open the browser **Developer Console** (F12 → Console), and refresh.
  2. You should see **`[Echo] Connected to 127.0.0.1:6001`** and **`[Echo] Subscribed to market.X`**. If you don’t, the JS bundle may be missing `MIX_PUSHER_*` or Soketi isn’t reachable – run **`npm run dev`** after setting `MIX_PUSHER_HOST` and `MIX_PUSHER_PORT` in `.env`.
  3. Place a bet (same or another tab). You should see **`[Echo] MarketOddsUpdated received`** in the console and the odds bars/¢ labels update. If “Subscribed” appears but “MarketOddsUpdated received” never does, Laravel isn’t sending: confirm **`BROADCAST_DRIVER=pusher`** and that **PUSHER_APP_KEY** / **PUSHER_APP_SECRET** match Soketi’s default (`app-key`, `app-secret`).

### WebSockets when using ngrok (e.g. on your phone)

When you open the app on your phone via ngrok (or another tunnel), the built-in Echo config points at `127.0.0.1:6001`, which on the phone is the phone itself—so the WebSocket never reaches your Soketi. To fix:

1. **Run two ngrok tunnels**: one for the Laravel app (e.g. port 8000) and one for Soketi (port 6001):
   ```bash
   # Terminal 1: Laravel
   php artisan serve
   ngrok http 8000

   # Terminal 2: Soketi
   npx @soketi/soketi start
   ngrok http 6001
   ```
2. **Set the Soketi public URL** in `.env` to the **second** ngrok URL (the one pointing at 6001):
   ```env
   SOKETI_PUBLIC_URL=https://your-soketi-subdomain.ngrok-free.app
   ```
   Use `https://` so the browser uses WSS. Don’t add a path or port (ngrok uses 443).

3. Reload the app on your phone. The layout injects this URL into the page, so Echo connects to your Soketi through ngrok and real-time odds work.

**Cache / “only works after hard refresh”**: The app adds a version query to `app.js` (file mtime or `APP_ASSET_VERSION`) so updates are loaded. If you still see old behaviour, do a hard refresh (Ctrl+Shift+R / Cmd+Shift+R) or clear the site’s cache.

## Flow

- **Admin**: Create a party → invite users (by email) → set default balance → create markets (yes/no or people; add options and images for people) → **Start pre-voting** → participants pre-vote (no tokens) → **Go live** → participants bet with fake $ → when time’s up, **Resolve** (official outcome or voting outcome) → payouts and leaderboard.

- **Real-time odds**: Poll `GET /api/markets/{id}/odds` or subscribe to the `market.{id}` channel (Laravel Echo + Pusher/Redis) when `MarketOddsUpdated` is broadcast.

- **Push notifications**: When a market is resolved, every user who placed a bet on that market receives a push notification (if they enabled push and VAPID is configured). Subscriptions are stored via `POST /push-subscription`. Generate VAPID keys with `php artisan web-push:vapid` and set `VAPID_SUBJECT`, `VAPID_PUBLIC_KEY`, and `VAPID_PRIVATE_KEY` in `.env`. Users must click “Enable push notifications” in the menu and accept the browser prompt; the front-end subscribes using the public key so the server can send.

## Routes (auth required except where noted)

- **Parties**: `GET /parties`, `GET /parties/{party}`, `POST /parties/{party}/markets/{market}/pre-vote`, `POST /parties/{party}/markets/{market}/bet`, `GET /parties/{party}/leaderboard`
- **Admin**: `GET /admin/parties`, `GET /admin/parties/create`, `POST /admin/parties`, `GET /admin/parties/{party}`, `POST /admin/parties/{party}/invite`, `POST /admin/parties/{party}/balance`, `POST /admin/parties/{party}/markets`, `POST /admin/parties/{party}/markets/{market}/options`, `POST /admin/parties/{party}/start-pre-voting`, `POST /admin/parties/{party}/start-live`, `POST /admin/parties/{party}/markets/{market}/resolve-official`, `POST /admin/parties/{party}/markets/{market}/resolve-voting`
- **API** (no auth): `GET /api/markets/{market}/odds`
- **Push**: `POST /push-subscription` (auth)

## Branding

Irish bold: dark green (`#0d3328`), gold (`#c9a227`), cream background, Bebas Neue + DM Sans. Nav and cards use shamrock theme classes in `resources/sass/app.scss`.

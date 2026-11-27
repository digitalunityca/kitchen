# Home Hub

The kitchen dashboard for the Fire HD 10 running Fully Kiosk. One Laravel app serves the page at `/`, and every card on it is a module.

**How it all fits together (structure, modules, Fully settings, what was built): [docs/PROJECT.md](docs/PROJECT.md).**

## Run it

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan serve --host=0.0.0.0 --port=8000
```

Point Fully's Start URL at `http://<server-ip>:8000`. The data is in MySQL 8.4, which compose starts as the `mysql` service: set the `DB_*` passwords in `.env` first (see `.env.example`). Sessions and the cache use files.

Add `?theme=night` or `?theme=day` to the URL to preview a look. Otherwise the dark look runs from 21:00 to 06:00 (`DASHBOARD_NIGHT_FROM` / `DASHBOARD_NIGHT_TO`).

## Run with Docker

```bash
cp .env.example .env    # then set APP_KEY: php artisan key:generate, or
                        # docker compose run --rm home-hub php artisan key:generate --show
docker compose up -d --build
```

The image (`Dockerfile`) installs dependencies without dev packages and serves the app with nginx + PHP-FPM ([serversideup/php](https://serversideup.net/open-source/docker-php/)) on port 8080 inside the container. `compose.yaml` publishes it on port 8000 (`HOME_HUB_PORT` changes that), reads settings from `.env`, restarts it unless you stop it, and keeps `storage/app` in the named volume `home-hub-data`, so saved playlists survive rebuilds.

After pulling code changes, run `docker compose up -d --build` again. Logs: `docker compose logs -f`.

For the tablet to reach it all day:
- Docker Desktop › Settings › General › **Start Docker Desktop when you sign in to your computer** must be on, so the container comes back after a reboot.
- The Mac must not sleep (System Settings › Energy: prevent automatic sleeping).
- Reserve the Mac's IP address on the router, since Fully's Start URL points at it.

### Cloudflare Tunnel (optional)

A [Cloudflare Tunnel](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/) serves the hub on a public https hostname without opening router ports, and keeps working when the tablet's Wi-Fi can't reach the server directly. home-hub has its own tunnel, so it moves with the repo and doesn't depend on other projects' tunnels.

One-time setup, on a machine where `cloudflared tunnel login` has been run (`~/.cloudflared/cert.pem` exists):

```bash
cloudflared --config deploy/cloudflared/config.yml tunnel create --credentials-file deploy/cloudflared/credentials.json home-hub
cloudflared --config deploy/cloudflared/config.yml tunnel route dns home-hub kitchen.<your-domain>
```

Keep the `--config deploy/cloudflared/config.yml`: without it cloudflared reads `~/.cloudflared/config.yml`, and if that file names another tunnel, `route dns` quietly points the hostname at that tunnel instead (the site then answers 404). Check with `dig` or the DNS tab: the CNAME must be `<HOME_HUB_TUNNEL_ID>.cfargotunnel.com`; fix with `route dns --overwrite-dns`.

Then in `.env`:

```
HOME_HUB_TUNNEL_ID=<the ID printed by "tunnel create">
HOME_HUB_ALLOWED_IPS=<your home's public IP, from: curl -s https://api.ipify.org>
HOME_HUB_PIN=<digits for opening the hub away from home; see Access>
APP_URL=https://kitchen.<your-domain>
```

Start it with `docker compose --profile tunnel up -d --build`, check `docker compose --profile tunnel logs cloudflared` for "Registered tunnel connection", and point Fully's Start URL at `https://kitchen.<your-domain>`.

- `deploy/cloudflared/config.yml` sends every hostname routed to the tunnel to `http://home-hub:8080` (the container's internal port, not 8000). `credentials.json` next to it is secret and gitignored; copy it along with `.env` when moving servers.
- Who gets in through the tunnel is covered in [Access](#access) below.
- The `cloudflared` service only runs with `--profile tunnel`, so plain `docker compose up -d` stays LAN-only. Laravel trusts the proxy headers (`bootstrap/app.php`), so links come out as https.

#### Access

Requests through the tunnel carry the visitor's address in `CF-Connecting-IP`; `RestrictTunnelVisitors` (`app/Http/Middleware`) decides what happens. Requests made directly on the LAN have no such header and aren't checked.

- **Home IP**: addresses in `HOME_HUB_ALLOWED_IPS` (IPs or CIDR ranges, comma-separated) get straight in, with no PIN. That's how the tablet loads the hostname. If your ISP changes the home IP, update the variable and run `docker compose --profile tunnel up -d`.
- **PIN**: anyone else is sent to `/unlock` and asked for `HOME_HUB_PIN` (digits). The right PIN sets an encrypted, HttpOnly, Secure, SameSite=Lax cookie for 30 days and returns to the page they asked for. The cookie is tied to the PIN, so changing `HOME_HUB_PIN` signs everyone out. Writes (POST etc.) without the cookie get 403.
- **No PIN set**: visitors outside the home IP get 403 (`/unlock` says it's only available from home). If both `HOME_HUB_ALLOWED_IPS` and `HOME_HUB_PIN` are empty, anyone with the hostname gets in and a warning is logged.
- **Rate limits** (keyed on `CF-Connecting-IP`, else the request IP; `app/Providers/AppServiceProvider.php`): every request counts towards 120 per minute per IP; `POST /unlock` allows 5 per minute; 10 wrong PINs lock that IP out of `/unlock` for 15 minutes. Over the limit, visitors get a 429 page.

Stronger options in Cloudflare, on top of the above:

- A rate-limiting rule (Security > WAF > Rate limiting rules), e.g. path equals `/unlock` and method `POST`, 5 requests per minute per IP, action Block, stops guessing before it reaches the hub.
- Cloudflare Access (Zero Trust > Access > Applications, self-hosted, hostname `kitchen.<your-domain>`) with a policy allowing your email addresses through the One-time PIN login, plus a Bypass policy for the home IP so the tablet keeps working without a login.

**Alternative: dashboard-managed tunnel (token).** Create the tunnel in Zero Trust > Networks > Tunnels, add a public hostname with service HTTP `home-hub:8080`, and replace the service's `command` and `volumes` in `compose.yaml` with `command: tunnel --no-autoupdate run` and `environment: { TUNNEL_TOKEN: ${CLOUDFLARE_TUNNEL_TOKEN} }`, with the token in `.env`.

### Offline page on the tablet

`deploy/offline/offline.html` is a standalone page (a family photo with a frosted clock card) that Fully shows when the dashboard can't load. It checks `https://kitchen.casahub.cloud/up` every 30 s and goes back to the dashboard once that answers.

1. Make `deploy/offline/photo.jpg`: a 1920x1200 JPEG. It's git-ignored, as is any `IMG_*.jpg`, so family photos stay out of the repo.
2. Push both files: `adb push deploy/offline/offline.html deploy/offline/photo.jpg /sdcard/Download/home-hub-offline/`
3. In Fully › Settings › Web Content Settings › Custom Error URL (PLUS), enter `file:///sdcard/Download/home-hub-offline/offline.html`.

Fully only switches to it on connection failures, for example when the home internet is down. When the tunnel or this server is down but the internet works, Cloudflare answers with its own Error 1033 page (HTTP 530), and Fully shows that page instead.

### Moving to another server

1. Copy or clone the repo to the new machine and copy `.env` over (it holds `APP_KEY`), plus `deploy/cloudflared/credentials.json` if you use the tunnel.
2. Copy the saved settings out of the old volume and into the new one:
   ```bash
   # old server
   docker compose cp home-hub:/var/www/html/storage/app/private/youtube-music.json .
   # new server
   docker compose up -d --build
   docker compose cp youtube-music.json home-hub:/var/www/html/storage/app/private/
   docker compose exec -u root home-hub chown www-data:www-data storage/app/private/youtube-music.json
   ```
3. Check `http://<new-ip>:8000` from a phone on the same Wi-Fi.
4. In Fully › Settings › Web Content Settings › Start URL, change the address to `http://<new-ip>:8000`.
5. Stop the old one with `docker compose down`.

## Modules

```
app/Dashboard/            Module base class, registry, service provider
app/Modules/<Name>/       One folder per module: <Name>Module.php plus any controllers
resources/views/modules/<key>/card.blade.php   The module's card
config/dashboard.php      Which modules are on, their order, grid position and settings
```

Add one:

```bash
php artisan make:module ShoppingList
```

Then add `App\Modules\ShoppingList\ShoppingListModule::class => ['column' => '1', 'row' => '1']` to `config/dashboard.php`. The grid has 3 columns and 2 rows, and `column`/`row` take CSS grid values such as `'1 / span 2'`.

A module can:
- pass data to its card with `data()`,
- read its config block with `$this->config('key')`,
- add routes in `routes()`, which are mounted at `/modules/<key>/...` and named `modules.<key>.*`,
- push CSS and JS with `@push('styles')` and `@push('scripts')` from its card,
- listen for `dashboard:tick` (fired every second with the current `Date`) instead of running its own timer.

## Weather

The weather card uses [Open-Meteo](https://open-meteo.com/), which is free and needs no API key. Set the location in `.env`, then rebuild with `docker compose --profile tunnel up -d --build`:

```
WEATHER_CITY=Ottawa
WEATHER_LAT=45.42
WEATHER_LON=-75.69
```

It shows the current temperature and condition, today's high and low, and the next 4 hours. Forecasts are cached for 15 minutes, and the card refreshes itself from `/modules/weather/now` (JSON) at the same interval. Until the coordinates are set, or whenever Open-Meteo fails, the card shows sample data with a "Sample data" pill.

In Fully Kiosk the card also writes a one-line summary to the action bar title, for example `⛅ 4° Partly cloudy · H 6° L −1° · 🌧️ Rain likely 3–6 PM`. The rain part (or snow) appears when hours later today have a precipitation probability of 50% or more. It names the first such stretch: `3–6 PM`, `until 4 PM` if it's already likely now, or `from 9 PM` if it lasts past midnight. This needs Toolbars and Appearance › Show Action Bar.

## YouTube Music

The card plays YouTube Music playlists through YouTube's official embedded player and draws its own controls (play/pause, next, previous, shuffle, playlist chips). Manage playlists from a phone at `/modules/youtube-music/settings`: paste a playlist or album share link.

What works and what doesn't on a Fire tablet in Fully:
- **music.youtube.com can't be embedded.** It refuses to load in a frame. "Open YouTube Premium" on the card opens YouTube's TV app (`https://www.youtube.com/tv`) full screen in Fully instead (see below).
- **Signing in to Google inside Fully is blocked** ("Couldn't sign you in. This browser or app may not be secure"). We tried Fake User Agent Edge, Firefox, and a Custom desktop Chrome string, with cookies cleared: each passed the email page and was blocked after it. The likely reason is that Fully runs on Android's WebView, which sends an `X-Requested-With` header. Fully 1.61.4 has no setting to turn that header off.
- **What works: YouTube's TV app with sign-in by code.** This gives Premium (no ads) and your own mixes, plus a Music section. Setup (done 2026-11-24):
  1. Fully › Settings › Advanced Web Settings › Fake User Agent String › **Custom User Agent**. The custom field stays greyed out until this is picked.
  2. Custom User Agent String (PLUS) = `Mozilla/5.0 (SMART-TV; Linux; Tizen 6.0) AppleWebKit/538.1 (KHTML, like Gecko) Version/6.0 TV Safari/538.1`
  3. Open `https://www.youtube.com/tv` › Get started › Sign in with your phone. On a phone, scan the QR code or go to `yt.be/activate`, enter the code shown, and pick the Premium account. No password is typed on the tablet. The sign-in survives reloads.
  This user agent applies to every page in Fully. The kitchen dashboard renders normally with it.
- **The TV app is tap-only.** Tiles, the sidebar, search and the player buttons (tap the video to show them) respond to taps. Swiping does not scroll, because the app moves focus like a TV remote. The tablet's Back button does little there (Escape is the TV app's Back). To return to the dashboard, swipe in from the left edge to open Fully's menu and tap **Goto Start URL**. Music stops when you leave the player.
- **The embedded player plays without your account**, so you get ads, no Premium, and no personal mixes. Playlists must be Public or Unlisted.
- **Some songs refuse to play outside YouTube** (label restriction, error 101/150). The card skips them and shows a short notice.
- **The page must send a Referer** or YouTube shows error 153. The page sets `strict-origin-when-cross-origin`. Don't load it as a local `file://` page.
- **The YouTube Music app** needs Google Play Services, which Fire OS doesn't have. It can be sideloaded, but that's a bigger change to the tablet.

Fully settings for audio: Web Content Settings › Autoplay Videos on, and keep Kiosk Mode's URL whitelist (if used) open to `youtube.com` (including `youtube.com/tv`) and `music.youtube.com`.

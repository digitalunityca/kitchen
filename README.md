# Home Hub

The kitchen dashboard for the Fire HD 10 running Fully Kiosk. One Laravel app serves the page at `/`, and every card on it is a module.

## Run it

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan serve --host=0.0.0.0 --port=8000
```

Point Fully's Start URL at `http://<server-ip>:8000`. No database is needed yet: sessions and cache use files, and module settings live in `storage/app/private/`.

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

A [Cloudflare Tunnel](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/) serves the hub on a public https hostname without opening router ports, and keeps working when the tablet's Wi-Fi can't reach the server directly.

1. In Cloudflare Zero Trust › Networks › Tunnels, create a tunnel (Cloudflared, Docker). Copy the token from the install command into `.env` as `CLOUDFLARE_TUNNEL_TOKEN=`.
2. Add a public hostname to the tunnel with service **HTTP** and URL **`home-hub:8080`** (the container name and its internal port, not 8000).
3. Start it: `docker compose --profile tunnel up -d`. Check with `docker compose --profile tunnel logs cloudflared` ("Registered tunnel connection").
4. Point Fully's Start URL at `https://<your-hostname>`.

The `cloudflared` service only runs with `--profile tunnel`, so plain `docker compose up -d` stays LAN-only. The hub has no login: anyone with the hostname can change its settings, so put a Cloudflare Access policy (Zero Trust › Access › Applications) in front of the hostname. Laravel trusts the proxy headers (`bootstrap/app.php`), so links come out as https.

### Moving to another server

1. Copy or clone the repo to the new machine and copy `.env` over (it holds `APP_KEY`).
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

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
- **music.youtube.com can't be embedded.** It refuses to load in a frame. "Open full app" on the card loads the full site in Fully instead (Back returns to the dashboard).
- **Signing in to Google inside Fully is blocked** by default ("This browser or app may not be secure"). Fully's documented workaround is Settings › Advanced Web Settings › Fake User Agent › Edge. Google may stop allowing this at any time.
- **The embedded player plays without your account**, so you get ads, no Premium, and no personal mixes. Playlists must be Public or Unlisted.
- **Some songs refuse to play outside YouTube** (label restriction, error 101/150). The card skips them and shows a short notice.
- **The page must send a Referer** or YouTube shows error 153. The page sets `strict-origin-when-cross-origin`. Don't load it as a local `file://` page.
- **The YouTube Music app** needs Google Play Services, which Fire OS doesn't have. It can be sideloaded, but that's a bigger change to the tablet.

Fully settings for audio: Web Content Settings › Autoplay Videos on, and keep Kiosk Mode's URL whitelist (if used) open to `youtube.com` and `music.youtube.com`.

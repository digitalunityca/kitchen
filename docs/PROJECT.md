# Home Hub: how it works

The kitchen tablet's dashboard: what it is, how the pieces connect, where the code lives, and what has been built so far. Plain English first; code paths are given so you can find things.

> No secrets in this file. Environment variables are listed by **name only**. The PIN, API keys, tunnel credentials and calendar links live in `.env` or `storage/app/private/` on the Mac, never in git.

---

## 1. Overview

- **The device:** an Amazon Fire HD 10 (2023) on the kitchen wall, running **Fully Kiosk Browser PLUS**. Fully shows one web page full screen and keeps it there.
- **The page:** a single Laravel app, *Home Hub*, served from the Mac at home. Everything on the screen (clock, weather, music, calendar, Canadiens, voice chat, kids' homework) is a **module** of that app.
- **Reaching it:** the tablet loads `https://kitchen.casahub.cloud`, a Cloudflare Tunnel to the Mac. Only the home's internet address gets straight in; anyone else needs the PIN.

---

## 2. How it works, end to end

```
Fire HD 10 ── Fully Kiosk ──https──▶ Cloudflare ──tunnel──▶ cloudflared (Docker) ──▶ home-hub (Docker: nginx + PHP-FPM, Laravel)
                                                                                      │
                                                      Open-Meteo · NHL API · OpenAI · iCloud/Google calendars · YouTube
```

### The tablet and Fully Kiosk
Fully settings that matter (changed on the tablet, not in code):

| Setting | Why |
|---|---|
| **Start URL** `https://kitchen.casahub.cloud/` (with the trailing slash) | The slash must match the page's real address, so Fully's "skip auto reload when showing the Start URL" works. Without it, Fully reloaded the dashboard every 30 minutes, which stopped the music. |
| **Auto reload after idle** (30 min) | Kept as a safety net for other pages; skipped on the dashboard (see above). |
| **Action Bar Title** | The dashboard writes the date, weather and Habs note there through Fully's JavaScript interface (`fully.setStringSetting('actionBarTitle', …)`). |
| **Custom Button Action** `javascript:openSettings()` | The ≡ button next to Fully's 🏠 opens the PIN-protected Settings sheet without a reload. Fully draws that button with its own icon, which can't be changed. |
| **Enable Microphone Access** + Android mic permission | For the Ask ChatGPT voice chat. |
| **Custom Error URL** (local `offline.html`) | Shown when the tablet can't reach the hub; it returns to the dashboard once `/up` answers. Source: `deploy/offline/`. |
| **Web content debugging** | Lets the Mac inspect and control the page over USB (adb), which is how it was tested. |

The dashboard also uses Fully's JavaScript interface for **volume** (`fully.setAudioVolume(level, 3)` sets the tablet's media volume), **speech** (`fully.textToSpeech` for spoken reminders) and **starting apps** (`fully.startApplication` for YouTube Music).

### The network: Cloudflare Tunnel
- `cloudflared` runs as a second container (Compose profile `tunnel`) and connects out to Cloudflare. Nothing on the home router is opened. Config: `deploy/cloudflared/config.yml` (the credentials file there is git-ignored).
- **Access rules** (`app/Actions/Access/`, `app/Http/Middleware/RestrictTunnelVisitors.php`):
  - From the home's public IP (`HOME_HUB_ALLOWED_IPS`): straight in.
  - From anywhere else: a PIN page (`HOME_HUB_PIN`) that sets a 30-day signed cookie. Changing the PIN signs everyone out.
  - Rate limits: 120 requests/minute per IP overall, 5 PIN tries/minute, and 10 wrong PINs lock that IP out for 15 minutes.
  - Requests made directly on the home network (not through the tunnel) aren't checked.

### The server: Docker on the Mac
- `compose.yaml` runs `home-hub` (built from `Dockerfile`: production dependencies only, nginx + PHP-FPM, port 8080 inside) and, with `--profile tunnel`, `cloudflared`.
- **Database: SQLite**, one file: `storage/app/database.sqlite` on the named volume `home-hub-data`, so it survives rebuilds (never in git). Migrations run when the container starts. Tables: `homework_tasks` (each day's sheets per kid), `homework_ledger` (points, + and −), `homework_words` (writing words), `kid_pins` (hashes only), `calendars` (including their secret links), `hub_settings` (sheet number counter, badge positions). Sessions and the cache stay in files.
  - **Backup = copy that one file**, e.g. `docker compose cp home-hub:/var/www/html/storage/app/database.sqlite ./backup.sqlite`.
  - Until Nov 27, 2026 the data was JSON files in `storage/app/private/` (`homework.json`, `homework-pins.json`, `calendars.json`). They're kept there untouched as a backup, with a copy in `storage/app/private/backup-pre-sqlite-*`. `php artisan hub:import-json` copies them into the database again (it replaces the database data with the files' content).
  - `unload.log` (in `storage/app/private/`): why the dashboard page unloaded (see below).
- **Unload log:** whenever the page unloads, the tablet sends a short report (how long it ran, whether music was playing, the last taps and setting writes) to `/diagnostics/unload`. That's how the 30-minute reload was traced and confirmed gone.

### Settings behind the PIN
`/settings` (opened in a sheet from Fully's ≡ button) asks for the same PIN on a number keypad (5 tries, then a 1-minute lockout). It stays unlocked for 10 minutes of use on that device (`app/Http/Middleware/RequireSettingsPin.php`). It contains Calendars, Homework and Kids (PINs). Those pages also need the PIN when opened from a phone.

---

## 3. Project structure

```
home-hub/
├── app/
│   ├── Actions/<Module>/        Business logic: one small class per job, one handle() method
│   │   ├── Access/              PIN, unlock cookie, lockouts, tunnel access decision
│   │   ├── AiChat/              Realtime sessions, moderation, web search, weather tool
│   │   ├── Calendar/            Fetch/parse iCal feeds, merge, add/update/remove calendars
│   │   ├── Dashboard/           Unload log
│   │   ├── Habs/                NHL schedule, highlights, game details, standings, stats sheet
│   │   ├── Homework/            (in progress) daily tasks, points, rewards, worksheets
│   │   ├── Weather/             Forecast fetch, parsing, rain note, summary line
│   │   └── YouTubeMusic/        YouTube Music app remote (MacroDroid link)
│   ├── Dashboard/               Module base class, ModuleRegistry, DashboardServiceProvider
│   ├── Http/
│   │   ├── Access/              Access settings and helpers (client IP, safe redirects)
│   │   ├── Controllers/         Dashboard, unlock, settings, diagnostics
│   │   └── Middleware/          Tunnel restriction, settings PIN, health-check CORS
│   └── Modules/<Name>/          One folder per module: the Module class, its controller(s),
│                                FormRequests (validation) and small helpers (stores, value objects)
├── config/dashboard.php         Which modules are on, their order, placement and settings
├── config/services.php          OpenAI settings
├── resources/views/
│   ├── dashboard/index.blade.php   The page: ticker strip, grid, side menu, overlays, shared JS
│   ├── modules/<key>/              Each module's card, sheets and settings page (HTML + its JS)
│   └── settings/                   PIN keypad and the Settings list
├── public/css/dashboard.css     Shared look (Apple-like, day and night themes)
├── routes/web.php               Dashboard, unlock, settings, diagnostics (modules add their own)
├── tests/                       PHPUnit: Feature (pages and endpoints) and Unit (actions);
│                                Fixtures/ holds trimmed real NHL and iCal responses
├── deploy/                      cloudflared config, the tablet's offline page
├── Dockerfile, compose.yaml     The production image and the two containers
├── stubs/module/                Template used by `php artisan make:module`
├── .github/workflows/tests.yml  CI: runs the test suite on PHP 8.3, 8.4 and 8.5
└── CLAUDE.md, .claude/skills/   Laravel Boost guidelines and skills for AI assistants
```

### How a module works
- Every module extends `App\Dashboard\Module` and declares a **key**, a **name**, its **data** for the view and optional **routes** (mounted under `/modules/<key>/…`).
- `config/dashboard.php` lists the enabled modules in display order, with each one's settings and **placement**:
  - `grid`: a card in the 3 × 2 grid (`column` / `row`)
  - `ticker`: a strip above the grid (Canadiens)
  - `menu`: a button in the right-side menu (ChatGPT mic, volume)
  - `overlay`: no card, only menu buttons and sheets (Homework)
  - Modules can also push buttons into the side menu (`@push('menu-items')`).
- `ModuleRegistry` builds the modules from config. `DashboardServiceProvider` lets each module register its services (e.g. its JSON store) and mounts its routes.
- `php artisan make:module Name` creates the class and a card view from `stubs/module/`.
- The page fires a `dashboard:tick` event every second (cards listen instead of running their own timers), plus `dashboard:home`, `dashboard:audio-claim` / `audio-release` (pause music for the voice chat) and `dashboard:duck` / `unduck` (lower it for reminder chimes). `Dashboard.setTitlePart()` composes Fully's top-bar title from the parts modules give it.

### The Actions convention
Controllers stay thin: validate (FormRequest or Validator), call **one action**, return a response. Each action is a plain class in `app/Actions/<Module>/` with constructor injection and a single `handle()` method. Outside services (HTTP, cache, files, config) are injected, so tests can fake them. Tests: one unit test per action, plus feature tests for each page and endpoint.

---

## 4. Modules

### Clock and greeting
The time, weekday and "Good morning / afternoon / evening" on the first card. The date itself is at the start of Fully's top bar ("Tuesday, November 24 · ☀️ 2° Clear · H 3° L −3°"), and it rolls over at midnight. The page switches to the dark look from 21:00 to 06:00.

### Weather
Current conditions, today's high and low, the next 4 hours and a rain/snow note ("Rain likely 3–6 PM") from **Open-Meteo** (free, no key), for the coordinates in `.env`. It refreshes every 15 minutes, and the hub caches it 15 minutes too. It also feeds the top-bar summary and the voice chat's weather tool (7-day forecast). Code: `app/Actions/Weather/`.

### YouTube Music (the app, through MacroDroid)
- The music card is a remote for the **YouTube Music app** on the tablet (Google Play sideloaded; YouTube Premium account). It shows the song playing ("title · artist"), big ⏮ ⏯ ⏭, and **Open YouTube Music** (launches the app through Fully; the tablet's ◀ Back returns to the dashboard). With nothing playing it shows only the open button.
- The link is **MacroDroid** on the tablet, two macros:
  - **Song to Hub**: on each YouTube Music notification, POSTs `title`/`artist` to `/modules/youtube-music/app/now-playing` with the `X-Hub-Token` header (`YTM_APP_TOKEN`).
  - **Music commands**: every 2 s, GETs `/modules/youtube-music/app/command` (same token) and presses Play/Pause, Next or Previous in YouTube Music. (MacroDroid's webhooks use Firebase push, which Fire OS blocks for a sideloaded Google Play services, hence polling.)
- The voice chat pauses the app while it talks and resumes it after.
- **Volume** is the slider in the right-side menu (the tablet's real media volume, through Fully).
- Code: `app/Modules/YouTubeMusic/MusicAppController.php`.

### Ask ChatGPT (voice)
- The blue mic in the side menu: tap to talk, tap again to end. It shows a spinner while connecting, rings that follow your voice, a glow while it answers, and captions in a bubble.
- **How:** the hub asks OpenAI for a one-minute **ephemeral session** (`app/Actions/AiChat/CreateRealtimeSession.php`). The tablet then talks to OpenAI's **Realtime API over WebRTC** directly, so the API key never leaves the hub. Music pauses during a chat, and the chat ends after 2 minutes of quiet.
- **Family-safe** (on by default): kid-safe rules are built into every session, and every spoken line (yours and its answers) is checked with OpenAI's moderation model. A flagged line stops the chat.
- **Tools:** `get_weather` (the hub's own forecast) and `search_web` (OpenAI Responses API with web search, localized to Sainte-Catherine), so it can answer current questions in English or French.

### Montreal Canadiens (Habs)
- A slim scrolling strip above the grid on game days: the game today or tomorrow (time, arena, TV), the live score with period and clock, or yesterday's final with W/L until 6 PM. There's no strip when no game is near.
- The **Canadiens logo** in the side menu (or tapping the strip) opens a full-size stats sheet with three tabs:
  - **Game:** score by period, goals with assists, shots, power play, goalies
  - **Standings:** division, conference or league, with the playoff line
  - **Schedule:** last 5 results and next 5 games
- Data comes from the NHL's public API, refreshed every 10 minutes (1–2 minutes around and during games). On game days, "🏒 Habs 7 PM" is added to the top bar.

### Calendar
- Calendars are connected by their **iCal links**: iCloud public links, Google "secret address in iCal format" (Google Workspace admins must allow it), public Google embed links, or Outlook ICS. Add them in Settings → Calendars with a name, colour, optional icon (⚽ 💼 💅 …) and optional reminder.
- **Card:** two weeks, with coloured rings around days that have events, then Today, Tomorrow and the next event after that. Finished events are muted; running ones get a "NOW" tag. A dropdown shows or hides calendars.
- **Month view** (calendar icon): month grid, a day's events, details, search across 6 months back to 12 months ahead, and calendar toggles.
- **Reminders:** a banner with a chime at the chosen time before an event, optionally spoken. (The shared links don't carry the calendars' own alarms.)
- **Refresh:** the hub re-reads each feed every 15 minutes and keeps the last good copy if one fails. The card refreshes itself every 5 minutes. Recurring events are handled by the `sabre/vobject` library.
- Code: `app/Actions/Calendar/`, `app/Modules/Calendar/`.

### Kids' homework
One round avatar button per kid in the side menu (Theo, Arthur; set in `config/dashboard.php`). Each opens the kid's own area in a near-full-screen overlay:
- **PIN screen:** avatar, name and a keypad. The kid's own 4-digit PIN opens it, and so does the parent PIN (`HOME_HUB_PIN`). A kid with no PIN yet sees "Ask a parent to set your PIN in Settings". Five wrong tries lock that kid out for a minute. Kid PINs are set in **Settings › Kids** and stored only as hashes in the `kid_pins` table.
- **Kid home:** points, today's progress ("1 of 2 checked today"), sheets waiting for a check, and four tiles: Homework, Rewards, History, News. The kid is signed out on ✕, on Home, or after 5 minutes without a tap.
- **Homework** (Monday to Friday): tap the tasks to do today (tap again for a second sheet, up to each task's daily limit), then **Print all**. Only then are the worksheets generated (OpenAI, `OPENAI_HOMEWORK_MODEL`), turned into one PDF and dropped in the Mac Studio's print folder (`HOMEWORK_PRINT_SPOOL`; `deploy/worksheet-print/`). The kid sees "Done! Your sheets are printing", and the tasks wait for a parent check. If any step fails, the picked tasks are taken back and the kid sees the error.
- **Rewards:** evening rewards (school days, once all of today's homework is checked) and weekend prizes. Each shows "You can get it now", "Yours once your homework is checked", or how many points are missing. A parent confirms each purchase with the parent PIN.
- **History:** "To check" (a parent approves each printed sheet with the parent PIN, or marks it "Not done"), and the points day by day. Each printed sheet also has a QR code a parent can scan to approve it.
- **News:** placeholder for messages from the kids' teachers (emails, to be connected later).
- Data: the SQLite tables `homework_*` and `kid_pins`. Code: `app/Actions/Homework/`, `app/Modules/Homework/` (`KidController` for the kid area, `HomeworkController` for worksheets and parent settings), `resources/views/modules/homework/kid/`.

---

## 5. Configuration (names only)

Set in `.env` on the Mac (template: `.env.example`).

| Variable | What it does |
|---|---|
| `APP_KEY`, `APP_URL`, `APP_ENV`, `APP_DEBUG` | Standard Laravel settings |
| `DASHBOARD_TITLE` | Browser tab title |
| `DASHBOARD_NIGHT_FROM`, `DASHBOARD_NIGHT_TO` | Hours of the dark look (default 21 to 6) |
| `HOME_HUB_ALLOWED_IPS` | Addresses that get in through the tunnel without the PIN (the home IP) |
| `HOME_HUB_PIN` | PIN for visitors outside home, for Settings, and the parent PIN for homework checks and rewards |
| `OPENAI_HOMEWORK_MODEL` | Model that writes the worksheets |
| `HOMEWORK_PRINT_SPOOL` | Folder in the container where print-ready PDFs go (mounted from the Mac) |
| `HOME_HUB_TUNNEL_ID` | Which Cloudflare Tunnel the `cloudflared` container runs |
| `WEATHER_CITY`, `WEATHER_LAT`, `WEATHER_LON` | Weather card label and location |
| `YTM_APP_TOKEN` | Shared secret MacroDroid sends to the music endpoints |
| `OPENAI_API_KEY` | Key for the voice chat (restricted key: Realtime, Moderations, Responses) |
| `OPENAI_REALTIME_MODEL`, `OPENAI_REALTIME_VOICE` | Voice chat model and voice |
| `OPENAI_SEARCH_MODEL` | Model behind the web search tool |
| `AI_CHAT_LOCATION` | Location given to the assistant (answers, search, weather) |
| `AI_CHAT_FAMILY_SAFE` | Kid-safe rules and moderation (on unless set to false) |
| `SESSION_DRIVER`, `CACHE_STORE`, `FILESYSTEM_DISK` | Files (no database) |

Module settings that aren't secrets (grid positions, kids, Habs, volume default) are in `config/dashboard.php`.

---

## 6. Running and deploying

On the Mac, from `home-hub/`:

```bash
docker compose --profile tunnel up -d --build home-hub
```

This rebuilds and restarts the app; the tablet picks up the new page on its next reload. The tablet can also be driven over USB with `adb` (screenshots, the page's developer tools).

- **Tests:** `php artisan test --compact` (PHPUnit; about 190 tests at the time of writing, with no network: NHL, OpenAI, weather and calendars are faked from fixtures).
- **Code style:** `vendor/bin/pint --dirty`
- **CI:** GitHub Actions runs the tests on every push to `main` and on pull requests (PHP 8.3, 8.4 and 8.5).
- **Logs:** `docker compose logs -f home-hub`; the app log is inside the container (reset on rebuild); the unload log is in `storage/app/private/unload.log` (kept).

---

## 7. What was done

**24 November 2026**
- **Foundation:** the module system and dashboard; Docker and Cloudflare Tunnel; PIN for outside access with rate limits; the offline page.
- **Weather:** real Open-Meteo forecast with icons and rain hours; summary in Fully's top bar.
- **YouTube Music:** first an embedded player with YouTube TV handoff; since Nov 27 a remote for the YouTube Music app through MacroDroid.
- **Code quality:** Laravel Boost (guidelines, skills, MCP); a SOLID/testing audit; business logic moved into Action classes; CI fixed to run on `main`; the lock file made installable on PHP 8.3.
- **Ask ChatGPT:** Realtime voice chat with ephemeral sessions; the mic button with animations and captions; family-safe rules and moderation; weather and web-search tools.
- **Canadiens:** game strip, live and final scores, stats sheet with Game, Standings and Schedule tabs; Fully top-bar note.
- **Layout:** the right-side menu (Home, kids, Canadiens, ChatGPT, volume); header removed and date moved to the top bar; volume slider driving the tablet's media volume; music resumes after reloads.
- **Reliability:** unload log (kept on the storage volume); found and removed the 30-minute reload (Start URL slash).
- **Calendar:** iCal calendars (iCloud, Google, Outlook) with settings, icons and colours; two-week card with rings; month view with search and toggles; next event; live refresh; reminders with chime and speech; fix for cached events.
- **Settings:** PIN keypad and Settings sheet, opened from Fully's top-bar button.
- **Kids:** homework buttons and sheet (homework system itself in progress, see above).

**25 November 2026**
- Canadiens sheet opens at full size with a centred spinner; homework sheet made near full screen, over the side menu.

(For every change, see `git log`.)

---

## 8. Known limits and next ideas

- **Fully's top-bar button** always shows Fully's own ≡ icon (no setting for its picture).
- **Shared calendar links** carry no alarms and often no notes; reminders come from the hub's own per-calendar setting. Work calendars on Google Workspace need the admin setting "Share all information" before a secret link is offered.
- **Unload log** is the only log kept across rebuilds; the app log resets with each container rebuild.
- **Homework:** the system in the working copy still needs reviewing and committing. Later phases: printing worksheets, more reward rules and history, and settings for templates.
- **Ideas:** timers by voice ("set a 10-minute timer"), the calendar as a voice-chat tool, and a Playwright smoke test for the dashboard's JavaScript (player, voice chat, sheets), which has no automated tests yet.

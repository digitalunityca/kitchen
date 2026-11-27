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
- **Database: MySQL 8.4 LTS**, the `mysql` service in `compose.yaml` (image `mysql:8.4.13`, data in the named volume `home-hub-mysql`, no port published outside Docker). Credentials are in `.env` (`DB_*`, see section 5). Migrations run when the home-hub container starts; it waits for MySQL to be healthy. Sessions and the cache stay in files.
  - **Tables:**
    - `kids`: slug, name, letter, colour, grade, menu order. Seeded once from `config/dashboard.php`; after that the database is the source.
    - `tasks`: each day's tasks per kid. The type (`task_types`: homework or home_help) says whether a row is a worksheet (linked to `exercise_types`, seeded from the templates) or a chore (linked to `chores`). Each row also has its points, the parent's score and when it was done and approved.
    - `chores` and `chore_kid`: the Aider à la maison list and which kid sees each chore.
    - `skills`, `skill_prerequisites`, `skill_grades`: the curriculum, loaded from `database/data/skills.json`.
    - `exercise_variants`: worksheet levels, loaded from `database/data/exercise_variants.json`.
    - `kid_skill_progress`: each kid's level and status per skill. Tasks keep the skill and level they were made for.
    - `homework_ledger`: points + and −; `at` is a UTC date-time. A chore's line points at its task (`task_ref`).
    - `homework_words`: writing words.
    - `kid_pins`: hashes only.
    - `calendars`: including their secret links.
    - `hub_settings`: the sheet number counter and badge positions.
  - **Foreign keys:** the four homework tables point at `kids` by `kid_id`, and a kid with data can't be deleted. Everything is utf8mb4.
  - **Backup** (from `home-hub/`):
    ```bash
    docker compose exec -T mysql sh -c 'exec mysqldump --single-transaction -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' > backup-$(date +%F).sql
    ```
    Restore with `docker compose exec -T mysql sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < backup.sql`.
  - **History:**
    - Nov 27, 2026, morning to afternoon: the data was in SQLite (`storage/app/database.sqlite` on the `home-hub-data` volume). The file is kept there untouched as the fallback. A dated copy, `backup-pre-mysql-20261127-1217.sqlite`, sits next to it and outside git in `../backups/`.
    - The move used a one-off command, `hub:copy-sqlite-to-mysql` (commit 5cfef8c). It copied every row and compared counts, totals and row digests on both sides. It was removed once the tables changed again; it is still in the git history.
    - Before that, the data was JSON files in `storage/app/private/`, kept there as a backup. `php artisan hub:import-json` reads them.
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
- **History:** "To check" lists printed sheets and chores, and the points day by day.
  - A parent approves each item with the parent PIN, or marks it "Not done".
  - A sheet's approval asks how it went: **Bien** or **Quelques erreurs** give the full points; **À revoir** gives half, rounded up. The score is saved with the sheet, to steer future levels (docs/LEARNING_PLAN.md).
  - Each printed sheet also has a QR code a parent can scan to approve it, with the same three choices.
- **Kid home:** the points once (big), one "Aujourd'hui" block (sheets checked, chores), today's sheets as icons on the Devoirs tile, and a slim Nouvelles bar.
  - Each kid has a background theme (`kids.theme`: `soccer`, `gaming`; first set from `config/dashboard.php`). The themes are original inline SVGs (resources/views/modules/homework/kid/themes/), soft behind translucent white cards, fainter outside the home screen.
- **Récompenses d'un parent:**
  - **Mission:** a parent gives one or more kids something to do, with points and an optional date. It shows on the kid's home (Aujourd'hui and a Missions block). The kid taps "J'ai fini"; a parent scores it in History with the PIN (Bien or Quelques erreurs: full points, À revoir: half), or marks it not done, which keeps it open.
  - **Bonus:** points given right away for something already done, from Settings or from History ("Récompense d'un parent", parent PIN, reason and +5/10/20/50).
  - Both are `parent_mission` tasks; the points go to the ledger and celebrate on the dashboard.
  - Settings › Récompenses d'un parent: give, see and take back missions. The QR approval page links to it.
- **Aider à la maison:** today's chores (seeded: mettre la table, desservir, faire son lit, préparer son sac at 5 points; ranger sa chambre, vider le lave-vaisselle, sortir le recyclage at 10).
  - The kid taps "J'ai fini"; a parent approves it in History; the points count toward rewards like any points, with a celebration on the dashboard.
  - Each chore counts a set number of times a day, on every day, school days or the weekend.
  - Settings › Aider à la maison edits the list, the points, the days and which kid sees each chore. A chore is turned off, never deleted.
- **News:** placeholder for messages from the kids' teachers (emails, to be connected later).
- **Curriculum and levels** (docs/LEARNING_PLAN.md):
  - **Catalogue:** `database/data/skills.json` holds 70 skills (47 math, 23 French), hand-encoded from Quebec's *Progression des apprentissages* (2009).
    - For each grade from 1re to 6e: → learns it with the teacher, ★ does it alone by June, or reuses it.
    - Month windows per grade (my estimate, editable in the file) and prerequisites.
    - Codes are ours: section letter + item number. The Marble taxonomy was only used as a reference.
  - **Levels:** `database/data/exercise_variants.json` holds 48 levels across addition, multiplication, division and the new *Grammaire et conjugaison* sheet (grades 2–6).
    - Additions and multiplications read the level's number ranges (and carrying). The lowest levels match the sheets made before levels existed.
    - Division and grammar send the level's focus to the AI. Reading and writing have no levels yet.
  - After editing either file, run `php artisan hub:sync-curriculum`. It loads both files; skills or levels removed from a file are turned off, and a prerequisite cycle is refused.
  - **Choosing a sheet** (`PickExerciseVariant`): the kid still taps a tile, and the tile shows the next level. The hub picks the skill and level in this order:
    1. the prerequisite, when the kid is stuck at level 1;
    2. a due review, or a skill marked "À revoir";
    3. this month's skills for the grade (★ before →, lowest level first);
    4. upkeep of mastered skills.
    - July and August: reviews and upkeep only.
    - Variety: a skill already on today's sheets goes last, and the same skill and level is skipped for 3 school days.
  - **After a scored check** (`UpdateSkillProgress`):
    - Bien twice in a row → next level. At the grade's top level → mastered, with reviews at +7 then +21 days.
    - Quelques erreurs → same level.
    - À revoir → one level down. Twice in a row → a note for the parent at the top of History.
  - **Starting levels** (`SeedKidProgress`, run by the migration):
    - Skills the grade only reuses start mastered.
    - Skills of this year start at level 1, or a little further into the year when they continue last year's →.
    - Sheets already approved count too: each old sheet matched the level with the same numbers.
  - **Settings › Progrès:** per kid, this month's skills, and each skill's level, status, last score and next review.
    - **Working grade:** "Les feuilles suivent" sets the grade the sheets follow (`kids.working_grade`; empty = school grade); `php artisan hub:working-grade arthur 3` does the same. The engine, the month windows, →/★, the tiles and the AI all use it.
      - Going up: skills the new grade only reuses are mastered; skills of the old grade that the new grade builds on are mastered (no step back); the new grade's skills start at level 1, or 2 when already started.
    - **Per skill:** − / + / Maîtrisé move a skill by hand (parent PIN).
  - **Known skills:** a skill under one the kid masters, or works on at level 2 or more, counts as known. It only comes back as upkeep, never as this month's work (no tables for a kid already doing 3 × 2 digits).
  - **Sheet sizes:** the printed sheet fills one Letter page, sized for handwriting (`SheetPage`: 532 × 590 pt under the header).
    - **Additions:** 7 mm per digit, 9.5 mm per written row, a carry row, room for the answer. Small sums give many problems; 4 digits or 3 numbers to add give fewer, bigger ones. 3 numbers are possible with `addends: 3` in a level.
    - **Multiplications:** square 8 mm cells. The rows are carries, the number, × the multiplier, then the result (1-digit multiplier) or one partial product per multiplier digit, a carry row for the sum and the total.
    - Old sheets with more problems than fit are drawn a little smaller, never on two pages.
  - **Divisions:** table-fact levels print as "56 ÷ 8 = ▢" lines (30 per page). Written-division levels (3 digits ÷ 1, 4 digits ÷ 2, decimal quotient) print in the Québec crochet layout, in 8 mm squares: dividend on the left, divisor behind the bracket, quotient under it, 2 rows per quotient digit. Both are made on the hub, with exact answers from the level's numbers. Only the story-based sharing levels (2e) still come from the AI.
  - **Écriture:** for each sheet the AI writes words for the kid's working grade (Québec French, accented, family-safe), none from the last 5 writing sheets. They are saved on the task, so reprints match. If the AI fails, a built-in list of 50 words is used.
    - Printed as a 4-column table, Mot | Écris | Mot | Écris: 15 rows of 12 mm, 30 words per page; each writing space has a baseline and a dashed midline.
    - The old per-kid word list in Settings was removed; its table stays, unused.
  - **AI sheets:** their own numbering ("1)") is removed. Division calculations are checked against the level (divisor, dividend or quotient, remainder): wrong answers are corrected, items outside the level are replaced by calculations made on the hub.
  - **PDF font:** the body prints in Helvetica; characters it lacks (→, ≠…) are drawn in DejaVu Sans (`PdfText`).
- Data: the MySQL tables `kids`, `homework_*` and `kid_pins`. Code: `app/Actions/Homework/`, `app/Modules/Homework/` (`KidController` for the kid area, `HomeworkController` for worksheets and parent settings), `resources/views/modules/homework/kid/`.

---

## 5. Configuration (names only)

Set in `.env` on the Mac (template: `.env.example`).

| Variable | What it does |
|---|---|
| `APP_KEY`, `APP_URL`, `APP_ENV`, `APP_DEBUG` | Standard Laravel settings |
| `APP_LOCALE` | Language of the hub: `fr` (default, Quebec French) or `en` |
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
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | The MySQL database (`mysql`, host `mysql`) |
| `DB_ROOT_PASSWORD` | MySQL root password, used only by the mysql container (backups, admin) |
| `SESSION_DRIVER`, `CACHE_STORE`, `FILESYSTEM_DISK` | Sessions and cache in files; app data in MySQL |

Module settings that aren't secrets (grid positions, kids, Habs, volume default) are in `config/dashboard.php`.

### Language

The hub speaks Quebec French by default (`APP_LOCALE=fr`), with English as the fallback.

- **Text:** every string is written in English in the code and wrapped in `__()`. `lang/fr.json` holds the French; a key missing there shows in English. `lang/en.json` holds only special keys (`weather.high`/`weather.low` → H/L).
- **JavaScript:** the dashboard and the packet page load the current JSON as `window.LANG`. `window.__(s)` translates, `window.LOCALE` (`fr-CA`/`en-CA`) formats dates, and `window.hm(date)` writes times as "17 h 45".
- **PHP times and dates:** `App\Support\TimeFormat` gives "17 h 45" in French. Carbon follows the app locale (`translatedFormat`, `isoFormat`).
- **Voice:** spoken messages use the `fr_CA` voice. The voice chat answers in Quebec French (or in the language it is spoken to), and transcription is set to French.
- **Printed sheets:** always in French (Quebec school).
- **Not translated:** NHL team, division and conference names; song titles; calendar events.
- **Tests:** they run in English (`APP_LOCALE=en` in `phpunit.xml`). `FrenchLocaleTest` checks the French pages and that `fr.json` has no empty value.

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

**27 November 2026**
- Working grade per kid (Arthur works at 3e); hand moves in Progrès; known-skill rule; sheets sized to fill the page; AI words for Écriture; checks on AI divisions.
- Curriculum-driven sheets: the skills catalogue from the Progression des apprentissages, levels in the sheet builders, the choice of skill and level, progress from the parent's score, the Progrès page, and the new Grammaire et conjugaison sheet.
- Parent's score on each approved sheet (Bien / Quelques erreurs / À revoir) and Aider à la maison (chores for points). Homework sheets became one type of task.
- French version (Quebec French), now the default language; English kept as fallback.
- Data moved from SQLite to MySQL 8.4, with a `kids` table and foreign keys.

(For every change, see `git log`.)

---

## 8. Known limits and next ideas

- **Fully's top-bar button** always shows Fully's own ≡ icon (no setting for its picture).
- **Shared calendar links** carry no alarms and often no notes; reminders come from the hub's own per-calendar setting. Work calendars on Google Workspace need the admin setting "Share all information" before a secret link is offered.
- **Unload log** is the only log kept across rebuilds; the app log resets with each container rebuild.
- **Homework:** the system in the working copy still needs reviewing and committing. Later phases: printing worksheets, more reward rules and history, and settings for templates.
- **Ideas:** timers by voice ("set a 10-minute timer"), the calendar as a voice-chat tool, and a Playwright smoke test for the dashboard's JavaScript (player, voice chat, sheets), which has no automated tests yet.

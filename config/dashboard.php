<?php

use App\Modules\AiChat\AiChatModule;
use App\Modules\Calendar\CalendarModule;
use App\Modules\Clock\ClockModule;
use App\Modules\Habs\HabsModule;
use App\Modules\Homework\HomeworkModule;
use App\Modules\Volume\VolumeModule;
use App\Modules\Weather\WeatherModule;
use App\Modules\YouTubeMusic\YouTubeMusicModule;

/*
| Kitchen dashboard.
|
| Modules render in the order listed. The grid is 3 columns x 2 rows; set
| 'column' and 'row' with CSS grid values (e.g. '1 / span 2') to place a card.
| Set 'enabled' => false to hide a module without deleting it, or
| 'placement' => 'menu' to show it as a button in the side menu instead of a card.
*/

return [
    'homework_print_spool' => env('HOMEWORK_PRINT_SPOOL'),

    'title' => env('DASHBOARD_TITLE', 'Kitchen'),

    // Visitors allowed through the Cloudflare Tunnel (comma-separated IPs or
    // CIDR ranges, e.g. the home's public IP). Empty = anyone with the link.
    // Requests made directly on the LAN are never checked.
    'allowed_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('HOME_HUB_ALLOWED_IPS', ''))))),

    // Tunnel visitors outside allowed_ips can unlock the hub with this numeric
    // PIN (README > Access). Empty = they get 403. Changing it signs everyone out.
    'pin' => (string) env('HOME_HUB_PIN', ''),

    // Dark look between these hours (24h clock, tablet's local time).
    'night' => [
        'from' => (int) env('DASHBOARD_NIGHT_FROM', 21),
        'to' => (int) env('DASHBOARD_NIGHT_TO', 6),
    ],

    'modules' => [

        ClockModule::class => [
            'column' => '1', 'row' => '1',
        ],

        WeatherModule::class => [
            'column' => '2', 'row' => '1',
            // Label on the card. Forecast from Open-Meteo for these coordinates
            // (decimal degrees, e.g. 45.42 / -75.69); empty = sample data.
            'city' => env('WEATHER_CITY', '[YOUR CITY]'),
            'lat' => env('WEATHER_LAT'),
            'lon' => env('WEATHER_LON'),
        ],

        // Calendars from iCal links, added on /modules/calendar/settings.
        CalendarModule::class => [
            'column' => '3', 'row' => '1 / span 2',
        ],

        // The music card: a remote for the YouTube Music app on the tablet, linked
        // through MacroDroid (MusicAppController). The token MacroDroid sends with
        // the song and when it fetches button presses is secret, in .env only.
        YouTubeMusicModule::class => [
            'column' => '1 / span 2', 'row' => '2',
            'app_package' => 'com.google.android.apps.youtube.music',
            'app_token' => (string) env('YTM_APP_TOKEN', ''),
        ],

        // One avatar button per kid in the side menu (under Home) for their homework.
        // 'badge' is the initial or an emoji shown on the button. 'grade' (Quebec primary,
        // 1-6) sizes the worksheets and picks the templates offered.
        HomeworkModule::class => [
            'kids' => [
                // theme: the background of the kid's area (soccer, gaming); the database keeps it after the first run.
                ['name' => 'Theo', 'badge' => 'T', 'color' => '#FF9500', 'grade' => 5, 'theme' => 'soccer'],
                ['name' => 'Arthur', 'badge' => 'A', 'color' => '#34C759', 'grade' => 2, 'theme' => 'gaming'],
            ],
        ],

        // Montreal Canadiens: a strip under the header on game days, and a
        // hockey button in the header for scores and stats any day.
        HabsModule::class => [],

        // "Ask ChatGPT" voice chat (OpenAI Realtime). Needs OPENAI_API_KEY in .env
        // (config/services.php). The location shapes the assistant's answers.
        // A blue mic button in the side menu on the right.
        AiChatModule::class => [
            'location' => env('AI_CHAT_LOCATION', 'Sainte-Catherine, Quebec'),
            // Children use the tablet: kid-safe persona rules plus an OpenAI
            // moderation check on every spoken line. Set false for adults-only.
            'family_safe' => (bool) env('AI_CHAT_FAMILY_SAFE', true),
        ],

        // Volume slider in the side menu, under the ChatGPT mic. In Fully it sets
        // the tablet's media volume; 'default' is only used in other browsers.
        VolumeModule::class => ['default' => 60],

    ],

];

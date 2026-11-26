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

        YouTubeMusicModule::class => [
            'column' => '1 / span 2', 'row' => '2',
            // Playlists added on /modules/youtube-music/settings are stored in
            // storage/app/youtube-music.json. These are only the first-run defaults.
            'playlists' => [
                // ['name' => 'Kitchen mix', 'id' => 'PLxxxxxxxx'],
            ],
            'volume' => 60,
            // "Open YouTube Premium" loads the YouTube TV app (youtube.com/tv), which
            // only serves TV browsers. In Fully Kiosk the button switches Fully's user
            // agent to this one for the trip; the dashboard switches it back on return.
            'tv_url' => 'https://www.youtube.com/tv',
            'tv_user_agent' => env('YOUTUBE_TV_USER_AGENT',
                'Mozilla/5.0 (SMART-TV; Linux; Tizen 6.0) AppleWebKit/538.1 (KHTML, like Gecko) Version/6.0 TV Safari/538.1'),
        ],

        // One avatar button per kid in the side menu (under Home) for their homework.
        // 'badge' is the initial or an emoji shown on the button. 'grade' (Quebec primary,
        // 1-6) sizes the worksheets and picks the templates offered.
        HomeworkModule::class => [
            'kids' => [
                ['name' => 'Theo', 'badge' => 'T', 'color' => '#FF9500', 'grade' => 5],
                ['name' => 'Arthur', 'badge' => 'A', 'color' => '#34C759', 'grade' => 2],
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

<?php

use App\Modules\AiChat\AiChatModule;
use App\Modules\Calendar\CalendarModule;
use App\Modules\Clock\ClockModule;
use App\Modules\Weather\WeatherModule;
use App\Modules\YouTubeMusic\YouTubeMusicModule;

/*
| Kitchen dashboard.
|
| Modules render in the order listed. The grid is 3 columns x 2 rows; set
| 'column' and 'row' with CSS grid values (e.g. '1 / span 2') to place a card.
| Set 'enabled' => false to hide a module without deleting it.
*/

return [

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

        // The AI Chat tile, off until the chat service exists. Turn it on and
        // move YouTube Music to column '1' to bring the old layout back.
        AiChatModule::class => [
            'enabled' => false,
            'column' => '2', 'row' => '2',
            'url' => env('AI_CHAT_URL', '#'),
        ],

    ],

];

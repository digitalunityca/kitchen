<?php

/*
| Kitchen dashboard.
|
| Modules render in the order listed. The grid is 3 columns x 2 rows; set
| 'column' and 'row' with CSS grid values (e.g. '1 / span 2') to place a card.
| Set 'enabled' => false to hide a module without deleting it.
*/

return [

    'title' => env('DASHBOARD_TITLE', 'Kitchen'),

    // Dark look between these hours (24h clock, tablet's local time).
    'night' => [
        'from' => (int) env('DASHBOARD_NIGHT_FROM', 21),
        'to' => (int) env('DASHBOARD_NIGHT_TO', 6),
    ],

    'modules' => [

        App\Modules\Clock\ClockModule::class => [
            'column' => '1', 'row' => '1',
        ],

        App\Modules\Weather\WeatherModule::class => [
            'column' => '2', 'row' => '1',
            'city' => env('WEATHER_CITY', '[YOUR CITY]'),
        ],

        App\Modules\Calendar\CalendarModule::class => [
            'column' => '3', 'row' => '1 / span 2',
        ],

        App\Modules\YouTubeMusic\YouTubeMusicModule::class => [
            'column' => '1 / span 2', 'row' => '2',
            // Playlists added on /modules/youtube-music/settings are stored in
            // storage/app/youtube-music.json. These are only the first-run defaults.
            'playlists' => [
                // ['name' => 'Kitchen mix', 'id' => 'PLxxxxxxxx'],
            ],
            'volume' => 60,
        ],

        // The AI Chat tile, off until the chat service exists. Turn it on and
        // move YouTube Music to column '1' to bring the old layout back.
        App\Modules\AiChat\AiChatModule::class => [
            'enabled' => false,
            'column' => '2', 'row' => '2',
            'url' => env('AI_CHAT_URL', '#'),
        ],

    ],

];

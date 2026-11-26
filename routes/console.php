<?php

use App\Modules\Habs\DemoGames;
use App\Modules\Habs\HabsModule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('habs:preview {state : upcoming, live, final or off}', function (string $state) {
    if ($state === 'off') {
        Cache::forget(HabsModule::PREVIEW_KEY);
        $this->info('Canadiens preview is off.');

        return;
    }
    if (! in_array($state, DemoGames::STATES, true)) {
        $this->error('Use upcoming, live, final or off.');

        return 1;
    }

    Cache::put(HabsModule::PREVIEW_KEY, $state, now('America/Toronto')->endOfDay());
    $this->info("Canadiens preview: {$state} sample game until midnight.");
})->purpose('Show a sample Canadiens game on the dashboard (until midnight)');

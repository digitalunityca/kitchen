<?php

namespace App\Modules\Volume;

use App\Dashboard\Module;

/**
 * A vertical volume slider in the side menu. In Fully Kiosk it sets the
 * tablet's media volume (music and the voice chat alike); in a normal
 * browser it tells the YouTube player through a dashboard:volume event.
 */
class VolumeModule extends Module
{
    public function key(): string
    {
        return 'volume';
    }

    public function name(): string
    {
        return 'Volume';
    }

    public function placement(): string
    {
        return 'menu';
    }

    /** @return array{default: int} */
    public function data(): array
    {
        return ['default' => (int) $this->config('default', 50)];
    }
}

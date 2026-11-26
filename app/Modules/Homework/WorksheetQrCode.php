<?php

declare(strict_types=1);

namespace App\Modules\Homework;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\URL;

final class WorksheetQrCode
{
    /** @return array{link: string, image: string} */
    public static function make(string $kid, string $date, array $task): array
    {
        $link = rtrim(config('app.url'), '/').URL::temporarySignedRoute(
            'modules.homework.packet.confirm',
            CarbonImmutable::parse($date, HomeworkModule::TIMEZONE)->addDays(HomeworkStore::KEEP_DAYS + 1),
            ['kid' => $kid, 'date' => $date, 'task' => $task['id'], 'seed' => $task['seed']],
            absolute: false,
        );

        $matrix = Encoder::encode($link, ErrorCorrectionLevel::M())->getMatrix();
        $size = $matrix->getWidth() + 8;
        $path = '';
        for ($y = 0; $y < $matrix->getHeight(); $y++) {
            for ($x = 0; $x < $matrix->getWidth(); $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $path .= 'M'.($x + 4).' '.($y + 4).'h1v1h-1z';
                }
            }
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 '.$size.' '.$size.'"><rect width="'.$size.'" height="'.$size.'" fill="white"/><path d="'.$path.'" fill="black"/></svg>';

        return ['link' => $link, 'image' => 'data:image/svg+xml;base64,'.base64_encode($svg)];
    }
}

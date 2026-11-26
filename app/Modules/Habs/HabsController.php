<?php

declare(strict_types=1);

namespace App\Modules\Habs;

use App\Actions\Habs\BuildHabsSheet;
use App\Actions\Habs\BuildHabsTicker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HabsController
{
    /** GET /modules/habs/ticker: the game side of the weather card (empty without a game today) and the action bar note. */
    public function ticker(Request $request, BuildHabsTicker $buildHabsTicker): JsonResponse
    {
        $ticker = $buildHabsTicker->handle(now()->toImmutable(), HabsModule::demo($request->query('habs')));

        return response()->json([
            'face' => $ticker['today'] ? view('modules.habs.face', ['item' => $ticker['today']])->render() : '',
            'title' => $ticker['title'],
        ])->header('Cache-Control', 'no-store');
    }

    /** GET /modules/habs/sheet: the stats sheet's HTML. */
    public function sheet(BuildHabsSheet $buildHabsSheet): Response
    {
        return response()->view('modules.habs.sheet', $buildHabsSheet->handle(now()->toImmutable()))->header('Cache-Control', 'no-store');
    }
}

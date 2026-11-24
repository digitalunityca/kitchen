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
    /** GET /modules/habs/ticker: the strip's HTML and the action bar note, for refreshing without a reload. */
    public function ticker(Request $request, BuildHabsTicker $buildHabsTicker): JsonResponse
    {
        $ticker = $buildHabsTicker->handle(now()->toImmutable(), $request->query('habs'));

        return response()->json([
            'html' => view('modules.habs.ticker', ['items' => $ticker['items']])->render(),
            'title' => $ticker['title'],
        ])->header('Cache-Control', 'no-store');
    }

    /** GET /modules/habs/sheet: the stats sheet's HTML. */
    public function sheet(BuildHabsSheet $buildHabsSheet): Response
    {
        return response()->view('modules.habs.sheet', $buildHabsSheet->handle(now()->toImmutable()))->header('Cache-Control', 'no-store');
    }
}

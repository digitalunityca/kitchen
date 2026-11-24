<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Dashboard\RecordPageUnload;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DiagnosticsController extends Controller
{
    /** POST /diagnostics/unload: a sendBeacon from the dashboard as it unloads (text/plain JSON, no CSRF token). */
    public function unload(Request $request, RecordPageUnload $recordPageUnload): Response
    {
        $report = json_decode(mb_substr($request->getContent(), 0, 8000), true);

        if (is_array($report)) {
            $recordPageUnload->handle($report);
        }

        return response('', 204);
    }
}

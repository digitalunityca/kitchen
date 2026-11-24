<?php

namespace App\Http\Controllers;

use App\Dashboard\ModuleRegistry;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(ModuleRegistry $modules): View
    {
        return view('dashboard.index', [
            'modules' => $modules->all(),
            'title' => config('dashboard.title'),
            'night' => config('dashboard.night'),
        ]);
    }
}

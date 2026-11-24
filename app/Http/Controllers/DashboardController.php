<?php

namespace App\Http\Controllers;

use App\Dashboard\ModuleRegistry;

class DashboardController extends Controller
{
    public function __invoke(ModuleRegistry $modules)
    {
        return view('dashboard.index', [
            'modules' => $modules->all(),
            'title' => config('dashboard.title'),
            'night' => config('dashboard.night'),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Homework;

use App\Actions\Homework\BuildWorksheet;
use App\Actions\Homework\LoadDailyHomework;
use App\Dashboard\ModuleRegistry;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeworkController extends Controller
{
    public function __construct(
        private readonly ModuleRegistry $modules,
        private readonly HomeworkStore $store,
        private readonly LoadDailyHomework $loadDailyHomework,
    ) {}

    /** GET …/{date}/{task}/worksheet: the printable sheet. */
    public function worksheet(string $kid, string $date, string $task, BuildWorksheet $buildWorksheet): View
    {
        return $this->renderWorksheet($kid, $date, $task, $buildWorksheet, answers: false);
    }

    /** GET …/{date}/{task}/answers: the same sheet with the answers filled in (PIN). */
    public function answers(string $kid, string $date, string $task, BuildWorksheet $buildWorksheet): View
    {
        return $this->renderWorksheet($kid, $date, $task, $buildWorksheet, answers: true);
    }

    public function settings(): View
    {
        $today = $this->today();

        return view('modules.homework.settings', [
            'today' => $today->toDateString(),
            'kids' => array_map(fn (array $kid): array => $kid + [
                'tasks' => $this->loadDailyHomework->handle($kid, $today),
                'balance' => $this->store->balance($kid['id']),
                'history' => $this->store->history($kid['id']),
            ], $this->module()->kids()),
            'templates' => HomeworkModule::TEMPLATES,
        ]);
    }

    /** GET …/kids-pins: each kid's homework PIN (set or not), behind the settings PIN. */
    public function pins(KidPinStore $pins): View
    {
        return view('modules.homework.pins', [
            'kids' => array_map(fn (array $kid): array => $kid + ['hasPin' => $pins->has($kid['id'])], $this->module()->kids()),
        ]);
    }

    /** POST …/kids/{kid}/pin (pin, or clear=1): set or clear a kid's 4-digit PIN. The PIN is stored hashed only. */
    public function savePin(Request $request, string $kid, KidPinStore $pins): RedirectResponse
    {
        $profile = $this->kidOrFail($kid);

        if ($request->boolean('clear')) {
            $pins->forget($profile['id']);

            return redirect()->route('modules.homework.pins')->with('status', __(':name’s PIN is cleared. The parent PIN still opens their homework.', ['name' => $profile['name']]));
        }

        $request->validate(['pin' => ['required', 'digits:4']], ['pin.digits' => __('The PIN must be exactly 4 digits.'), 'pin.required' => __('Type a 4-digit PIN.')]);
        $pins->put($profile['id'], (string) $request->input('pin'));

        return redirect()->route('modules.homework.pins')->with('status', __(':name’s PIN is saved.', ['name' => $profile['name']]));
    }

    private function renderWorksheet(string $kid, string $date, string $task, BuildWorksheet $buildWorksheet, bool $answers): View
    {
        $profile = $this->kidOrFail($kid);
        $found = collect($this->store->tasks($profile['id'], $date) ?? [])->firstWhere('id', $task);
        abort_if($found === null, 404);

        if (isset($found['worksheet'])) {
            return view('modules.homework.generated', [
                'kid' => $profile,
                'date' => $date,
                'tasks' => [$found],
                'answers' => $answers,
            ]);
        }

        abort_unless(HomeworkModule::TEMPLATES[$found['template']]['printable'], 404);

        return view('modules.homework.worksheet', [
            'kid' => $profile,
            'task' => $found,
            'template' => HomeworkModule::TEMPLATES[$found['template']],
            'date' => CarbonImmutable::parse($date, HomeworkModule::TIMEZONE)->locale('fr_CA'),
            'sections' => $buildWorksheet->handle($found['template'], $profile['workGrade'] ?? $profile['grade'], (int) $found['seed'], $this->store->words($profile['id'])),
            'answers' => $answers,
        ]);
    }

    /** @return array{id: string, name: string, badge: string, color: string, grade: int} */
    private function kidOrFail(string $id): array
    {
        $kid = $this->module()->kid($id);
        abort_if($kid === null, 404);

        return $kid;
    }

    private function module(): HomeworkModule
    {
        $module = $this->modules->get('homework');
        abort_unless($module instanceof HomeworkModule, 404);

        return $module;
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now(HomeworkModule::TIMEZONE)->startOfDay();
    }
}

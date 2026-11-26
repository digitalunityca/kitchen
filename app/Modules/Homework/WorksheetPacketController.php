<?php

declare(strict_types=1);

namespace App\Modules\Homework;

use App\Actions\Access\AttemptSettingsUnlock;
use App\Actions\Homework\GenerateWorksheetPacket;
use App\Actions\Homework\QueueWorksheetPrint;
use App\Actions\Homework\ReviewTask;
use App\Dashboard\ModuleRegistry;
use App\Http\Access\AccessGate;
use App\Http\Access\AccessSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WorksheetPacketController
{
    public function __construct(
        private readonly ModuleRegistry $modules,
        private readonly HomeworkStore $store,
    ) {}

    public function show(Request $request, string $kid, string $date): Response
    {
        $profile = $this->profile($kid);
        $tasks = $this->packetTasks($kid, $date);
        $needsGeneration = (bool) array_filter($tasks, fn (array $task): bool => ! isset($task['worksheet']));

        $view = $request->boolean('summary') ? 'modules.homework.summary' : 'modules.homework.generated';

        return response()->view($view, [
            'kid' => $profile,
            'date' => $date,
            'tasks' => $tasks,
            'answers' => false,
            'macPrint' => true,
            'needsGeneration' => $needsGeneration,
        ])->header('Cache-Control', 'no-store');
    }

    public function generate(string $kid, string $date, GenerateWorksheetPacket $generate): Response
    {
        abort_unless($date === now(HomeworkModule::TIMEZONE)->toDateString(), 404);
        abort_unless($generate->handle($this->profile($kid), $date), 409, 'Worksheets are already being generated. Try again shortly.');

        return response()->noContent();
    }

    public function print(string $kid, string $date, string $job, QueueWorksheetPrint $print): JsonResponse
    {
        $profile = $this->profile($kid);
        $tasks = $this->packetTasks($kid, $date);
        abort_if($tasks === [] || array_filter($tasks, fn (array $task): bool => ! isset($task['worksheet'])), 409, 'The worksheet packet is not ready.');

        $print->handle($job, $profile, $date, $tasks);

        return response()->json(['state' => $print->state($job)], 202);
    }

    public function printStatus(string $kid, string $date, string $job, QueueWorksheetPrint $print): JsonResponse
    {
        $this->profile($kid);

        return response()->json(['state' => $print->state($job)])->header('Cache-Control', 'no-store');
    }

    private function packetTasks(string $kid, string $date): array
    {
        return array_values(array_filter($this->store->tasks($kid, $date) ?? [], fn (array $task): bool => $task['status'] !== 'approved'));
    }

    public function confirm(string $kid, string $date, string $task, Request $request, AccessSettings $access, AttemptSettingsUnlock $unlock, ReviewTask $review): Response
    {
        abort_unless($access->pinEnabled(), 403);
        $profile = $this->profile($kid);
        $found = $this->printedTask($kid, $date, $task);
        $error = null;
        $status = 200;

        if ($request->isMethod('POST') && $found['status'] !== 'approved') {
            $pin = $request->input('pin');
            $result = $unlock->handle(AccessGate::clientIp($request), is_string($pin) && strlen($pin) <= 64 ? $pin : '');

            if ($result['ok']) {
                $review->handle($kid, $date, $task, approve: true, printedSeed: (int) $found['seed']);
                $found = $this->printedTask($kid, $date, $task);
            } else {
                $error = $result['lockedFor'] ? "Too many tries. Wait {$result['lockedFor']} seconds." : 'Incorrect parent PIN.';
                $status = $result['lockedFor'] ? 429 : 403;
            }
        }

        return response()->view('modules.homework.confirm', [
            'kid' => $profile,
            'task' => $found,
            'date' => $date,
            'error' => $error,
        ], $status)->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    private function printedTask(string $kid, string $date, string $id): array
    {
        $task = collect($this->store->tasks($kid, $date) ?? [])->firstWhere('id', $id);
        abort_unless(isset($task['worksheet']) && (string) $task['seed'] === request()->query('seed'), 404);

        return $task;
    }

    private function profile(string $kid): array
    {
        $module = $this->modules->get('homework');
        $profile = $module instanceof HomeworkModule ? $module->kid($kid) : null;
        abort_if($profile === null, 404);

        return $profile;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Homework;

use App\Modules\Homework\HomeworkModule;
use App\Modules\Homework\HomeworkStore;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * "Print all" on the kid's Homework screen: add the picked tasks, generate their
 * worksheets (only now, never before), send the packet to the Mac Studio's print
 * queue, and put the tasks in "waiting for a parent check". If any step fails the
 * picked tasks are taken back, so the kid can simply try again.
 */
class PrintHomework
{
    public function __construct(
        private readonly HomeworkStore $store,
        private readonly AddHomeworkTasks $addHomeworkTasks,
        private readonly GenerateWorksheetPacket $generateWorksheetPacket,
        private readonly QueueWorksheetPrint $queueWorksheetPrint,
    ) {}

    /**
     * @param  array{id: string, name: string, grade: int}  $kid
     * @param  array<string, int>  $counts  template => how many
     * @return int how many worksheets were sent to the printer
     *
     * @throws ValidationException when nothing is picked, a rule is broken, or generating/printing fails
     */
    public function handle(array $kid, CarbonInterface $today, array $counts): int
    {
        $counts = array_filter($counts, fn (int $count): bool => $count > 0);
        if ($counts === []) {
            throw ValidationException::withMessages(['print' => 'Pick some homework first.']);
        }
        if (! $this->queueWorksheetPrint->available()) {
            throw ValidationException::withMessages(['print' => 'The printer isn’t reachable right now. Ask a parent.']);
        }

        $date = $today->toDateString();
        $before = array_column($this->store->tasks($kid['id'], $date) ?? [], 'id');
        $this->store->synchronized(fn () => $this->addHomeworkTasks->handle($kid, $today, $counts));
        $picked = array_values(array_diff(array_column($this->store->tasks($kid['id'], $date) ?? [], 'id'), $before));

        try {
            if (! $this->generateWorksheetPacket->handle($kid, $date, $picked)) {
                throw ValidationException::withMessages(['print' => 'Your sheets are already being made. Try again in a minute.']);
            }

            $this->store->numberSheets();
            $tasks = array_values(array_filter($this->store->tasks($kid['id'], $date) ?? [], fn (array $t): bool => in_array($t['id'], $picked, true)));
            $this->queueWorksheetPrint->handle((string) Str::uuid(), $kid, $date, $tasks);
        } catch (\Throwable $e) {
            $this->store->update(function (array &$data) use ($kid, $date, $picked): void {
                $data['days'][$date][$kid['id']] = array_values(array_filter(
                    $data['days'][$date][$kid['id']] ?? [],
                    fn (array $t): bool => ! in_array($t['id'], $picked, true),
                ));
            });

            throw $e;
        }

        $now = now(HomeworkModule::TIMEZONE)->toIso8601String();
        $this->store->update(function (array &$data) use ($kid, $date, $picked, $now): void {
            foreach ($data['days'][$date][$kid['id']] ?? [] as $i => $task) {
                if (in_array($task['id'], $picked, true) && $task['status'] === 'todo') {
                    $data['days'][$date][$kid['id']][$i]['status'] = 'done';
                    $data['days'][$date][$kid['id']][$i]['printedAt'] = $now;
                }
            }
        });

        return count($picked);
    }
}

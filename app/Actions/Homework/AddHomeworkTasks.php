<?php

declare(strict_types=1);

namespace App\Actions\Homework;

use App\Modules\Homework\HomeworkModule;
use App\Modules\Homework\HomeworkStore;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/**
 * The kid picks today's homework: how many of each template. Only school days
 * (Monday to Friday), only templates for the kid's grade, and never more than a
 * template's daily limit (e.g. one reading). Each task keeps a seed, so its
 * worksheet prints the same problems every time.
 */
class AddHomeworkTasks
{
    public function __construct(private readonly HomeworkStore $store) {}

    /**
     * @param  array{id: string, grade: int}  $kid
     * @param  array<string, int>  $counts  template => how many to add
     *
     * @throws ValidationException when the pick breaks a rule
     */
    public function handle(array $kid, CarbonInterface $date, array $counts): void
    {
        if ($date->isWeekend()) {
            throw ValidationException::withMessages(['counts' => 'Homework is Monday to Friday.']);
        }

        $day = $date->toDateString();
        $tasks = $this->store->tasks($kid['id'], $day) ?? [];
        $templates = HomeworkModule::templatesFor($kid['grade']);
        $next = collect($tasks)->map(fn (array $t): int => (int) substr($t['id'], 1))->max() ?? 0;

        foreach ($counts as $template => $count) {
            $count = (int) $count;
            if ($count <= 0) {
                continue;
            }

            if (! isset($templates[$template])) {
                throw ValidationException::withMessages(['counts' => 'That homework isn’t available.']);
            }

            $already = collect($tasks)->where('template', $template)->count();
            if ($already + $count > $templates[$template]['max']) {
                throw ValidationException::withMessages(['counts' => "Only {$templates[$template]['max']} {$templates[$template]['name']} per day."]);
            }

            for ($i = 0; $i < $count; $i++) {
                $next++;
                $tasks[] = [
                    'id' => 't'.$next,
                    'template' => $template,
                    'points' => $templates[$template]['points'],
                    'status' => 'todo',
                    'seed' => random_int(0, PHP_INT_MAX),
                ];
            }
        }

        $this->store->putTasks($kid['id'], $day, $tasks);
    }
}

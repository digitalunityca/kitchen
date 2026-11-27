<?php

declare(strict_types=1);

namespace App\Actions\Learning;

use Illuminate\Support\Facades\DB;

/** The prerequisite graph: every skill a skill builds on, directly or not. */
class Prerequisites
{
    /** @var array<int, list<int>>|null */
    private ?array $edges = null;

    /**
     * @param  iterable<int>  $skillIds
     * @return list<int> all their prerequisites, at any depth (the skills themselves excluded)
     */
    public function below(iterable $skillIds): array
    {
        $this->edges ??= DB::table('skill_prerequisites')->get()->groupBy('skill_id')
            ->map(fn ($rows) => $rows->pluck('prerequisite_id')->map(fn ($id): int => (int) $id)->all())->all();

        $seen = [];
        $todo = [];
        foreach ($skillIds as $id) {
            $todo[] = (int) $id;
        }
        while ($todo !== []) {
            foreach ($this->edges[array_pop($todo)] ?? [] as $pre) {
                if (! isset($seen[$pre])) {
                    $seen[$pre] = true;
                    $todo[] = $pre;
                }
            }
        }

        return array_keys($seen);
    }
}

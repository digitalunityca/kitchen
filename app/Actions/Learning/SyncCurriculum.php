<?php

declare(strict_types=1);

namespace App\Actions\Learning;

use App\Models\ExerciseType;
use App\Models\ExerciseVariant;
use App\Models\Skill;
use App\Modules\Homework\HomeworkModule;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Loads the curriculum files into the database: the worksheet types (from
 * HomeworkModule::TEMPLATES), the skills with their grade expectations, month
 * windows and prerequisites (database/data/skills.json), and the worksheet
 * levels (database/data/exercise_variants.json). Safe to run again: rows are
 * matched by code, removed ones are turned off, never deleted (progress and
 * History keep pointing at them). A prerequisite cycle stops the sync.
 */
class SyncCurriculum
{
    public const EXPECTATIONS = ['>' => 'apprend', '*' => 'maitrise', '+' => 'reinvestit'];

    /** Default month windows when the file gives none: → from November, ★ all year, reuse = upkeep only. */
    private const DEFAULT_WINDOWS = ['apprend' => [11, 6], 'maitrise' => [9, 6], 'reinvestit' => [null, null]];

    private const NAMES_FR = ['Addition' => 'Addition', 'Multiplication' => 'Multiplication', 'Division' => 'Division', 'Writing' => 'Écriture', 'Reading' => 'Lecture', 'Grammar' => 'Grammaire'];

    /** @return array{skills: int, variants: int} */
    public function handle(?string $skillsFile = null, ?string $variantsFile = null): array
    {
        $catalogue = $this->read($skillsFile ?? database_path('data/skills.json'))['skills'];
        $variants = $this->read($variantsFile ?? database_path('data/exercise_variants.json'))['variants'];
        $this->assertNoCycle($catalogue);

        DB::transaction(function () use ($catalogue, $variants): void {
            $this->syncExerciseTypes();

            foreach ($catalogue as $s) {
                Skill::query()->updateOrCreate(['code' => $s['code']], [
                    'subject' => $s['subject'], 'domain' => $s['domain'], 'name_fr' => $s['name'],
                    'source' => 'pda', 'source_ref' => $s['ref'] ?? null, 'active' => true,
                ]);
            }
            Skill::query()->whereNotIn('code', array_column($catalogue, 'code'))->update(['active' => false]);
            $ids = Skill::query()->pluck('id', 'code')->all();

            DB::table('skill_grades')->delete();
            DB::table('skill_prerequisites')->delete();
            foreach ($catalogue as $s) {
                foreach (str_split($s['marks']) as $i => $mark) {
                    if (! isset(self::EXPECTATIONS[$mark])) {
                        continue;
                    }
                    $grade = $i + 1;
                    $expectation = self::EXPECTATIONS[$mark];
                    [$start, $end] = $s['windows'][(string) $grade] ?? self::DEFAULT_WINDOWS[$expectation];
                    DB::table('skill_grades')->insert(['skill_id' => $ids[$s['code']], 'grade' => $grade, 'expectation' => $expectation, 'window_start' => $start, 'window_end' => $end]);
                }
                foreach ($s['prereqs'] ?? [] as $code) {
                    DB::table('skill_prerequisites')->insert(['skill_id' => $ids[$s['code']], 'prerequisite_id' => $ids[$code], 'strength' => 'hard']);
                }
            }

            $types = ExerciseType::query()->pluck('id', 'slug')->all();
            $kept = [];
            foreach ($variants as $v) {
                $variant = ExerciseVariant::query()->updateOrCreate(
                    ['exercise_type_id' => $types[$v['type']], 'skill_id' => $ids[$v['skill']], 'level' => $v['level']],
                    ['grades_from' => $v['grades'][0], 'grades_to' => $v['grades'][1], 'label_fr' => $v['label'], 'params' => $v['params'], 'active' => true],
                );
                $kept[] = $variant->id;
            }
            ExerciseVariant::query()->whereNotIn('id', $kept)->update(['active' => false]);
        });

        return ['skills' => count($catalogue), 'variants' => count($variants)];
    }

    private function syncExerciseTypes(): void
    {
        $position = 0;
        foreach (HomeworkModule::TEMPLATES as $slug => $t) {
            ExerciseType::query()->updateOrCreate(['slug' => $slug], [
                'name_fr' => self::NAMES_FR[$t['name']] ?? $t['name'], 'emoji' => $t['emoji'],
                'subject' => in_array($slug, ['reading', 'writing', 'grammar'], true) ? 'francais' : 'math',
                'generator' => match ($slug) {
                    'addition' => 'addition_columns', 'multiplication' => 'multiplication_grid', default => 'ai_packet'
                },
                'points' => $t['points'], 'grades_from' => $t['grades'][0], 'grades_to' => $t['grades'][1], 'printable' => $t['printable'],
                'position' => $position++, 'active' => true,
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function read(string $path): array
    {
        $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        return is_array($data) ? $data : throw new RuntimeException("Unreadable curriculum file: {$path}");
    }

    /**
     * A skill can't (even indirectly) be its own prerequisite.
     *
     * @param  list<array<string, mixed>>  $catalogue
     */
    private function assertNoCycle(array $catalogue): void
    {
        $edges = [];
        foreach ($catalogue as $s) {
            $edges[$s['code']] = $s['prereqs'] ?? [];
        }
        $state = [];
        $visit = function (string $code, array $path) use (&$visit, &$state, $edges): void {
            if (($state[$code] ?? null) === 'done') {
                return;
            }
            if (($state[$code] ?? null) === 'open') {
                throw new RuntimeException('Prerequisite cycle: '.implode(' → ', [...$path, $code]));
            }
            if (! array_key_exists($code, $edges)) {
                throw new RuntimeException("Unknown prerequisite: {$code}");
            }
            $state[$code] = 'open';
            foreach ($edges[$code] as $next) {
                $visit($next, [...$path, $code]);
            }
            $state[$code] = 'done';
        };
        foreach (array_keys($edges) as $code) {
            $visit($code, []);
        }
    }
}

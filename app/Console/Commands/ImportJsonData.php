<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\KidPin;
use App\Modules\Calendar\CalendarStore;
use App\Modules\Homework\HomeworkStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * One-off move of the old JSON stores (storage/app/private/*.json) into the
 * database. Safe to run again: each run replaces the database copy with the
 * files' content. The files are left untouched as a backup. Prints counts to
 * compare, never secrets.
 */
class ImportJsonData extends Command
{
    protected $signature = 'hub:import-json';

    protected $description = 'Import the old JSON data files (homework, kid PINs, calendars) into the database';

    public function handle(HomeworkStore $homework, CalendarStore $calendars): int
    {
        $disk = Storage::disk('local');
        $read = function (string $file) use ($disk): ?array {
            if (! $disk->exists($file)) {
                return null;
            }
            $data = json_decode((string) $disk->get($file), true);

            return is_array($data) ? $data : null;
        };

        DB::transaction(function () use ($read, $homework, $calendars): void {
            if (($data = $read('homework.json')) !== null) {
                $homework->save([
                    'days' => (array) ($data['days'] ?? []),
                    'ledger' => array_values((array) ($data['ledger'] ?? [])),
                    'words' => (array) ($data['words'] ?? []),
                    'sheets' => (int) ($data['sheets'] ?? 0),
                    'seen' => (array) ($data['seen'] ?? []),
                ]);
                $this->line('Homework: imported');
            }

            if (($pins = $read('homework-pins.json')) !== null) {
                KidPin::query()->delete();
                foreach ($pins as $kid => $hash) {
                    if (is_string($hash)) {
                        KidPin::query()->create(['kid' => (string) $kid, 'hash' => $hash]);
                    }
                }
                $this->line('Kid PINs: '.KidPin::query()->count().' imported (hashes only)');
            }

            if (($list = $read('calendars.json')) !== null) {
                $calendars->save(array_values($list));
                $this->line('Calendars: '.count($calendars->all()).' imported');
            }
        });

        $state = $homework->load();
        $tasks = collect($state['days'])->flatMap(fn (array $kids): array => array_merge(...array_values(array_map('array_values', $kids))));
        $this->table(['Check', 'Value'], [
            ['Days kept', count($state['days'])],
            ['Tasks', $tasks->count()],
            ['Tasks by status', $tasks->countBy('status')->map(fn (int $n, string $s): string => "{$s}: {$n}")->implode(', ')],
            ['Numbered sheets', $tasks->whereNotNull('number')->count().' (highest #'.str_pad((string) ($tasks->max('number') ?? 0), 4, '0', STR_PAD_LEFT).', counter '.$state['sheets'].')'],
            ['Ledger lines', count($state['ledger'])],
            ['Points per kid', collect($state['ledger'])->groupBy('kid')->map(fn ($e, string $k): string => $k.' '.$e->sum('points'))->implode(', ')],
            ['Word lists', count($state['words'])],
        ]);

        return self::SUCCESS;
    }
}

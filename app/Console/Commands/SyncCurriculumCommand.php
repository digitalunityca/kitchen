<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Learning\SyncCurriculum;
use Illuminate\Console\Command;

/** Reload the skills and worksheet levels after editing database/data/*.json. */
class SyncCurriculumCommand extends Command
{
    protected $signature = 'hub:sync-curriculum';

    protected $description = 'Load database/data/skills.json and exercise_variants.json into the database';

    public function handle(SyncCurriculum $sync): int
    {
        $counts = $sync->handle();
        $this->info("Skills: {$counts['skills']}, worksheet levels: {$counts['variants']}");

        return self::SUCCESS;
    }
}

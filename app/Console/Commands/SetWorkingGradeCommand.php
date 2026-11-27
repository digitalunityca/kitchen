<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Learning\SetWorkingGrade;
use App\Models\Kid;
use Illuminate\Console\Command;

/** Set the grade a kid's sheets follow (same as Settings › Progrès). */
class SetWorkingGradeCommand extends Command
{
    protected $signature = 'hub:working-grade {kid : the kid\'s slug, e.g. theo} {grade : 1 to 6}';

    protected $description = 'Set the grade a kid’s worksheets follow (the school grade stays)';

    public function handle(SetWorkingGrade $set): int
    {
        $kid = Kid::query()->where('slug', (string) $this->argument('kid'))->firstOrFail();
        $set->handle($kid, (int) $this->argument('grade'));
        $kid->refresh();
        $this->info("{$kid->slug}: works at grade {$kid->workGrade()} (school grade {$kid->grade})");

        return self::SUCCESS;
    }
}

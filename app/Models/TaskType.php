<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A kind of task: homework sheets, household help ("Aider à la maison"). */
class TaskType extends Model
{
    public const HOMEWORK = 'homework';

    public const HOME_HELP = 'home_help';

    public const PARENT_MISSION = 'parent_mission';

    protected $guarded = [];

    /** @var array<string, int> */
    private static array $ids = [];

    public static function idFor(string $slug): int
    {
        return self::$ids[$slug] ??= (int) static::query()->where('slug', $slug)->value('id');
    }

    /** Forget cached ids (tests rebuild the database). */
    public static function flushIds(): void
    {
        self::$ids = [];
    }
}

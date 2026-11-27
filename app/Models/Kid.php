<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Homework\HomeworkModule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

/**
 * A kid of the household: their slug in the URLs ("theo"), name, avatar letter,
 * colour, school grade and order in the side menu. The rows come from
 * config/dashboard.php the first time (seedFromConfig); after that the
 * database is the source.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $badge
 * @property string $color
 * @property int $grade
 * @property int|null $working_grade
 * @property int $position
 */
class Kid extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['grade' => 'integer', 'working_grade' => 'integer', 'position' => 'integer'];
    }

    /** The grade the sheets follow: the working grade a parent set, else the school grade. */
    public function workGrade(): int
    {
        return $this->working_grade ?? $this->grade;
    }

    /** Add the kids listed in the config that have no row yet. */
    public static function seedFromConfig(): void
    {
        foreach (array_values((array) config('dashboard.modules.'.HomeworkModule::class.'.kids', [])) as $position => $kid) {
            $name = (string) $kid['name'];
            static::query()->firstOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'badge' => (string) ($kid['badge'] ?? mb_substr($name, 0, 1)),
                'color' => (string) ($kid['color'] ?? '#5AC8FA'),
                'grade' => (int) ($kid['grade'] ?? 2),
                'position' => $position,
            ]);
        }
    }

    /** The row id for a slug ("theo" → 1). */
    public static function idFor(string $slug): int
    {
        return (int) (static::query()->where('slug', $slug)->value('id') ?? throw (new ModelNotFoundException)->setModel(static::class, [$slug]));
    }

    /** @return array<int, string> id → slug */
    public static function slugs(): array
    {
        return static::query()->pluck('slug', 'id')->all();
    }
}

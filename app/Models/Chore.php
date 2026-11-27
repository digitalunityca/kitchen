<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A way to help at home ("Mettre la table"): its points, how many times a day
 * it counts, on which days, and which kids see it.
 *
 * @property int $id
 * @property string $slug
 * @property string $name_fr
 * @property string $emoji
 * @property int $points
 * @property int $per_day
 * @property string $days all | school | weekend
 * @property bool $active
 */
class Chore extends Model
{
    public const DAYS = ['all', 'school', 'weekend'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['points' => 'integer', 'per_day' => 'integer', 'position' => 'integer', 'active' => 'boolean'];
    }

    /** @return BelongsToMany<Kid, $this> */
    public function kids(): BelongsToMany
    {
        return $this->belongsToMany(Kid::class);
    }

    /** Active chores this kid sees on that day, in their order. */
    public function scopeFor(Builder $query, int $kidId, CarbonInterface $day): void
    {
        $query->where('active', true)
            ->whereIn('days', ['all', $day->isWeekend() ? 'weekend' : 'school'])
            ->whereHas('kids', fn (Builder $q) => $q->whereKey($kidId))
            ->orderBy('position')->orderBy('id');
    }
}

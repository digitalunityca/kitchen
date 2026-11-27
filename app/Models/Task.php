<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One task of one kid on one day: a homework sheet or a household chore
 * (task_type). "data" keeps the whole task as the homework code uses it.
 *
 * @property int $id
 * @property int $kid_id
 * @property int $task_type_id
 * @property int|null $chore_id
 * @property string $task_id
 * @property string $status
 * @property int $points
 * @property string|null $score
 */
class Task extends Model
{
    protected $table = 'tasks';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'kid_id' => 'integer', 'task_type_id' => 'integer', 'exercise_type_id' => 'integer', 'chore_id' => 'integer',
            'points' => 'integer', 'number' => 'integer', 'data' => 'array', 'date' => 'date:Y-m-d',
            'done_at' => 'datetime', 'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Kid, $this> */
    public function kid(): BelongsTo
    {
        return $this->belongsTo(Kid::class);
    }

    /** @return BelongsTo<Chore, $this> */
    public function chore(): BelongsTo
    {
        return $this->belongsTo(Chore::class);
    }
}

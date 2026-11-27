<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    protected $table = 'homework_ledger';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = [];

    /** One line of a kid's points: + for an approved sheet (task_id), − for a reward (reward_id). */
    protected function casts(): array
    {
        return ['kid_id' => 'integer', 'points' => 'integer', 'at' => 'datetime'];
    }

    /** @return BelongsTo<Kid, $this> */
    public function kid(): BelongsTo
    {
        return $this->belongsTo(Kid::class);
    }
}

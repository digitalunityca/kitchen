<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        return ['points' => 'integer'];
    }
}

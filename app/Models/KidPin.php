<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KidPin extends Model
{
    protected $table = 'kid_pins';

    protected $primaryKey = 'kid_id';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = [];
    /* A kid's homework PIN, bcrypt hash only. */

    protected function casts(): array
    {
        return ['kid_id' => 'integer'];
    }

    /** @return BelongsTo<Kid, $this> */
    public function kid(): BelongsTo
    {
        return $this->belongsTo(Kid::class);
    }
}

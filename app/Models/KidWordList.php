<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KidWordList extends Model
{
    protected $table = 'homework_words';

    protected $primaryKey = 'kid_id';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = [];

    /** A kid's words to copy on the writing sheets. */
    protected function casts(): array
    {
        return ['kid_id' => 'integer', 'words' => 'array'];
    }

    /** @return BelongsTo<Kid, $this> */
    public function kid(): BelongsTo
    {
        return $this->belongsTo(Kid::class);
    }
}

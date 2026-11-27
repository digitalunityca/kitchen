<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    protected $table = 'calendars';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    /** A connected calendar; "data" holds its settings, including the secret iCal address. */
    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}

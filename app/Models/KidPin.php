<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KidPin extends Model
{
    protected $table = 'kid_pins';

    protected $primaryKey = 'kid';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];
    /* A kid's homework PIN, bcrypt hash only. */
}

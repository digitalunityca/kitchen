<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KidWordList extends Model
{
    protected $table = 'homework_words';

    protected $primaryKey = 'kid';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    /** A kid's words to copy on the writing sheets. */
    protected function casts(): array
    {
        return ['words' => 'array'];
    }
}

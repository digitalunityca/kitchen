<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeworkTask extends Model
{
    protected $table = 'homework_tasks';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $guarded = [];

    /** One picked task of one day; "data" holds the whole task as the homework code uses it. */
    protected function casts(): array
    {
        return ['data' => 'array', 'date' => 'date:Y-m-d'];
    }
}

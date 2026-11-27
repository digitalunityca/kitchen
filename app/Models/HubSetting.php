<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HubSetting extends Model
{
    protected $table = 'hub_settings';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    /** Small saved values: the sheet number counter, each kid's "seen" ledger position. */
    public static function get(string $key, ?string $default = null): ?string
    {
        return static::query()->find($key)?->value ?? $default;
    }

    public static function put(string $key, string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}

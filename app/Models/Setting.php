<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static $runtimeCache = [];

    public static function get(string $key, $default = null)
    {
        if (! array_key_exists($key, static::$runtimeCache)) {
            static::$runtimeCache[$key] = static::query()->where('key', $key)->value('value');
        }

        $v = static::$runtimeCache[$key];

        return $v !== null && $v !== '' ? $v : $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        unset(static::$runtimeCache[$key]);
    }

    public static function clearRuntimeCache(): void
    {
        static::$runtimeCache = [];
    }
}

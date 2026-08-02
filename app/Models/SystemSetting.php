<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group'];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function put(string $key, mixed $value, string $type = 'string', string $group = 'platform'): self
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => ['data' => $value], 'type' => $type, 'group' => $group]
        );
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return self::where('key', $key)->value('value')['data'] ?? $default;
    }
}

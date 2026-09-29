<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $table = 'system_settings';

    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    /**
     * Decode json value if applicable.
     */
    public function getValueAttribute(mixed $val): mixed
    {
        if (is_null($val)) {
            return null;
        }

        $decoded = json_decode($val, true);

        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $val;
    }

    /**
     * Always JSON encode value to preserve types (boolean, numbers, arrays, strings).
     */
    public function setValueAttribute(mixed $val): void
    {
        $this->attributes['value'] = json_encode($val);
    }
}

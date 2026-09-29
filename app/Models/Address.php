<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'address_line_1',
        'address_line_2',
        'landmark',
        'city',
        'state',
        'country',
        'pincode',
        'latitude',
        'longitude',
        'is_default',
        'is_site_address',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_site_address' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

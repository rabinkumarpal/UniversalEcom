<?php

namespace Packages\PromotionEngine\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'promotion_id',
        'user_id',
        'reward_type',
        'reward_payload',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'reward_payload' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

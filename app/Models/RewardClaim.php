<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $reward_id
 * @property string $voucher_code
 * @property int $points_spent
 * @property string $status
 * @property Carbon|null $expires_at
 * @property Carbon|null $redeemed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class RewardClaim extends Model
{
    protected $fillable = [
        'user_id',
        'reward_id',
        'voucher_code',
        'points_spent',
        'status',
        'expires_at',
        'redeemed_at',
    ];

    protected function casts(): array
    {
        return [
            'points_spent' => 'integer',
            'expires_at' => 'datetime',
            'redeemed_at' => 'datetime',
        ];
    }

    /**
     * User who claimed the voucher.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Reward associated with this claim.
     *
     * @return BelongsTo<Reward, $this>
     */
    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }
}

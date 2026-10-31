<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $points
 * @property string $type
 * @property string $description
 * @property int|null $created_by_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PointTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'points',
        'type',
        'description',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
        ];
    }

    /**
     * User owning this transaction.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Staff/Admin user who initiated an adjustment (null if system generated).
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}

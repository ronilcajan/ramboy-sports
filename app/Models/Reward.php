<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $category
 * @property int $points_cost
 * @property int|null $stock
 * @property string|null $badge_text
 * @property string $icon
 * @property string|null $terms
 * @property int|null $venue_id
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Reward extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'points_cost',
        'stock',
        'badge_text',
        'icon',
        'terms',
        'venue_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'points_cost' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Reward $reward): void {
            if (empty($reward->slug)) {
                $reward->slug = Str::slug($reward->name).'-'.Str::random(6);
            }
        });
    }

    /**
     * Venue this reward belongs to (null if universal for all venues).
     *
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Voucher claims for this reward.
     *
     * @return HasMany<RewardClaim, $this>
     */
    public function claims(): HasMany
    {
        return $this->hasMany(RewardClaim::class);
    }

    /**
     * Scope query to active rewards only.
     *
     * @param  Builder<Reward>  $query
     * @return Builder<Reward>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to rewards visible for a given venue (universal or matching venue_id).
     *
     * @param  Builder<Reward>  $query
     * @return Builder<Reward>
     */
    public function scopeForVenue(Builder $query, ?int $venueId): Builder
    {
        if ($venueId === null) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($venueId): void {
            $q->whereNull('venue_id')->orWhere('venue_id', $venueId);
        });
    }
}

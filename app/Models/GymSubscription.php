<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GymSubscription extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'gym_id',
        'subscription_plan_id',
        'starts_at',
        'ends_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GymSubscription $subscription) {
            $subscription->starts_at ??= now()->toDateString();

            if (! $subscription->ends_at && $subscription->plan) {
                $subscription->ends_at = $subscription->starts_at
                    ->copy()
                    ->addDays($subscription->plan->duration_days);
            }
        });
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    protected function daysRemaining(): Attribute
    {
        return Attribute::get(fn (): int => (int) now()->startOfDay()->diffInDays($this->ends_at, false));
    }

    protected function isExpired(): Attribute
    {
        return Attribute::get(fn (): bool => $this->status !== self::STATUS_CANCELLED
            && $this->ends_at->isBefore(now()->startOfDay()));
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', self::STATUS_ACTIVE)
            ->whereDate('ends_at', '>=', now());
    }

    public function scopeExpiringWithin(Builder $query, int $days): void
    {
        $query->where('status', self::STATUS_ACTIVE)
            ->whereDate('ends_at', '>=', now())
            ->whereDate('ends_at', '<=', now()->addDays($days));
    }

    public function scopeExpired(Builder $query): void
    {
        $query->where('status', '!=', self::STATUS_CANCELLED)
            ->whereDate('ends_at', '<', now());
    }
}

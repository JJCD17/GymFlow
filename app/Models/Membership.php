<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Membership extends Model
{
    use BelongsToGym, HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'gym_id',
        'member_id',
        'plan_id',
        'starts_at',
        'ends_at',
        'status',
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
        static::creating(function (Membership $membership) {
            $membership->starts_at ??= now()->toDateString();

            if (! $membership->ends_at && $membership->plan) {
                $membership->ends_at = $membership->starts_at
                    ->copy()
                    ->addDays($membership->plan->duration_days);
            }
        });
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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

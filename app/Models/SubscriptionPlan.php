<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'duration_days',
        'price',
        'is_trial',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_trial' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SubscriptionPlan $plan) {
            $plan->sort_order ??= static::nextSortOrder();
        });

        // Un plan ya contratado es la referencia del historial de ese gimnasio.
        // La regla vive aquí y no solo en la pantalla, para que ninguna otra
        // vía lo borre por descuido.
        static::deleting(function (SubscriptionPlan $plan) {
            if ($plan->subscriptions()->exists()) {
                return false;
            }
        });
    }

    protected static function nextSortOrder(): int
    {
        return (int) static::max('sort_order') + 1;
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(GymSubscription::class);
    }

    public function isDeletable(): bool
    {
        return ! $this->subscriptions()->exists();
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}

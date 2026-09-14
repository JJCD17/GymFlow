<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use BelongsToGym, HasFactory;

    protected $fillable = [
        'gym_id',
        'name',
        'duration_days',
        'price',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Un plan nuevo va al final de la lista. Nadie eligió su posición, así
        // que colarse arriba sería una decisión que el dueño no tomó.
        static::creating(function (Plan $plan) {
            $plan->sort_order ??= static::nextSortOrder($plan->gym_id);
        });

        // Un plan vendido es la referencia del historial de esos clientes.
        // La regla vive aquí y no solo en la pantalla, para que ninguna otra
        // vía lo borre por descuido.
        static::deleting(function (Plan $plan) {
            if ($plan->memberships()->exists()) {
                return false;
            }
        });
    }

    protected static function nextSortOrder(?int $gymId): int
    {
        return (int) static::withoutGlobalScope('gym')
            ->where('gym_id', $gymId)
            ->max('sort_order') + 1;
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function isDeletable(): bool
    {
        return ! $this->memberships()->exists();
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Member extends Model
{
    use BelongsToGym, HasFactory;

    protected $fillable = [
        'gym_id',
        'full_name',
        'phone',
        'email',
        'photo_path',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function currentMembership(): HasOne
    {
        return $this->hasOne(Membership::class)->latestOfMany('ends_at');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    public function lastCheckIn(): HasOne
    {
        return $this->hasOne(CheckIn::class)->latestOfMany('checked_in_at');
    }

    public function hasCheckedInToday(): bool
    {
        return $this->checkIns()->whereDate('checked_in_at', now())->exists();
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * `$expiringDays` es el ajuste del gimnasio. Se recibe en vez de leerse
     * aquí porque un scope no sabe de qué gimnasio es la consulta.
     */
    public function scopeWithMembershipStatus(Builder $query, string $status, ?int $expiringDays = null): void
    {
        $expiringDays ??= Gym::DEFAULT_EXPIRING_DAYS;

        $query->whereHas('currentMembership', fn (Builder $q) => match ($status) {
            'active' => $q->whereDate('ends_at', '>=', now()),
            'expiring' => $q->whereDate('ends_at', '>=', now())
                ->whereDate('ends_at', '<=', now()->addDays($expiringDays)),
            'expired' => $q->whereDate('ends_at', '<', now()),
        });
    }

    public function scopeInactiveSince(Builder $query, Carbon $date): void
    {
        $query->where(fn (Builder $q) => $q
            ->whereDoesntHave('checkIns')
            ->orWhereHas('lastCheckIn', fn (Builder $c) => $c->whereDate('checked_in_at', '<', $date))
        );
    }

    protected function membershipStatus(): Attribute
    {
        return Attribute::get(function (): string {
            $endsAt = $this->currentMembership?->ends_at;

            return match (true) {
                ! $endsAt => 'none',
                $endsAt->isBefore(now()->startOfDay()) => 'expired',
                ! $endsAt->isAfter(today()->addDays($this->gym->expiring_days)) => 'expiring',
                default => 'active',
            };
        });
    }
}

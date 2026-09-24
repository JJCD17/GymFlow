<?php

namespace App\Models;

use App\Support\MessageTemplates;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Gym extends Model
{
    use HasFactory;

    public const DEFAULT_EXPIRING_DAYS = 7;

    protected $fillable = [
        'name',
        'code',
        'phone',
        'logo_path',
        'timezone',
        'suspended_at',
        'inactivity_days',
        'expiring_days',
        'closed_weekdays',
        'message_expiring',
        'message_expired',
        'message_inactive',
    ];

    protected function casts(): array
    {
        return [
            'suspended_at' => 'datetime',
            'inactivity_days' => 'integer',
            'expiring_days' => 'integer',
            'closed_weekdays' => 'array',
        ];
    }

    /**
     * Un gimnasio entra al sistema solo si nadie lo suspendió a mano y su
     * suscripción sigue vigente. Se calcula en vez de guardarse: así no hay
     * una columna que pueda quedar desfasada de la fecha de vencimiento.
     */
    protected function isActive(): Attribute
    {
        return Attribute::get(fn (): bool => ! $this->isSuspended() && $this->hasActiveSubscription());
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    public function activeSubscription(): ?GymSubscription
    {
        return $this->subscriptions()->active()->latest('ends_at')->first();
    }

    /**
     * La última suscripción registrada, vigente o no: es la que hay que
     * mostrar para explicar por qué un gimnasio está fuera.
     */
    public function latestSubscription(): ?GymSubscription
    {
        return $this->subscriptions()->latest('ends_at')->first();
    }

    public function isClosedOn(Carbon $date): bool
    {
        return in_array($date->dayOfWeek, $this->closed_weekdays ?? [], false);
    }

    /**
     * Fecha a partir de la cual un cliente cuenta como ausente, saltando los
     * días que el gimnasio no abre: si cierra domingos, no deben sumarse al
     * conteo de días sin asistir.
     */
    public function absenceThresholdDate(): Carbon
    {
        $date = now()->startOfDay();
        $openDays = 0;

        while ($openDays < $this->inactivity_days) {
            $date->subDay();

            if (! $this->isClosedOn($date)) {
                $openDays++;
            }
        }

        return $date;
    }

    protected function messageExpiring(): Attribute
    {
        return Attribute::get(fn (?string $value) => $value ?: MessageTemplates::expiring());
    }

    protected function messageExpired(): Attribute
    {
        return Attribute::get(fn (?string $value) => $value ?: MessageTemplates::expired());
    }

    protected function messageInactive(): Attribute
    {
        return Attribute::get(fn (?string $value) => $value ?: MessageTemplates::inactive());
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->where('role', User::ROLE_OWNER);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(GymSubscription::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    public function scopeSuspended(Builder $query): void
    {
        $query->whereNotNull('suspended_at');
    }

    /** Sin suspender a mano y con suscripción vigente. */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('suspended_at')
            ->whereHas('subscriptions', fn (Builder $sub) => $sub->active());
    }

    /** Fuera del sistema, sea por suspensión o por vencimiento. */
    public function scopeInactive(Builder $query): void
    {
        $query->where(fn (Builder $group) => $group
            ->whereNotNull('suspended_at')
            ->orWhereDoesntHave('subscriptions', fn (Builder $sub) => $sub->active()));
    }

    public function scopeExpiringWithin(Builder $query, int $days): void
    {
        $query->whereNull('suspended_at')
            ->whereHas('subscriptions', fn (Builder $sub) => $sub->expiringWithin($days));
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }
}

<?php

namespace App\Models;

use App\Support\MessageTemplates;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Gym extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'phone',
        'logo_path',
        'timezone',
        'is_active',
        'inactivity_days',
        'message_expiring',
        'message_expired',
        'message_inactive',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'inactivity_days' => 'integer',
        ];
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

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
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

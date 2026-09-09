<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}

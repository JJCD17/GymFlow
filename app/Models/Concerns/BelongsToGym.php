<?php

namespace App\Models\Concerns;

use App\Models\Gym;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToGym
{
    protected static function bootBelongsToGym(): void
    {
        static::addGlobalScope('gym', function (Builder $query) {
            if ($gymId = static::currentGymId()) {
                $query->where($query->getModel()->getTable().'.gym_id', $gymId);
            }
        });

        static::creating(function ($model) {
            if (! $model->gym_id && $gymId = static::currentGymId()) {
                $model->gym_id = $gymId;
            }
        });
    }

    protected static function currentGymId(): ?int
    {
        if (! Auth::hasUser()) {
            return null;
        }

        $user = Auth::user();

        return $user->isSuperAdmin() ? null : $user->gym_id;
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }
}

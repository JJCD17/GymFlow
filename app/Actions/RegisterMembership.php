<?php

namespace App\Actions;

use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;

class RegisterMembership
{
    public function handle(Member $member, Plan $plan, float $amount, string $method, ?string $startsAt = null): Membership
    {
        return DB::transaction(function () use ($member, $plan, $amount, $method, $startsAt) {
            $membership = $member->memberships()->create([
                'plan_id' => $plan->id,
                'starts_at' => $startsAt ?? $this->nextStartDate($member),
            ]);

            Payment::create([
                'member_id' => $member->id,
                'membership_id' => $membership->id,
                'amount' => $amount,
                'method' => $method,
                'paid_at' => now(),
            ]);

            return $membership;
        });
    }

    protected function nextStartDate(Member $member): string
    {
        $current = $member->currentMembership;

        // Renovar antes de vencer no debe regalar ni quitar días: la nueva
        // membresía arranca cuando termina la vigente.
        if ($current && $current->ends_at->isFuture()) {
            return $current->ends_at->copy()->addDay()->toDateString();
        }

        return now()->toDateString();
    }
}

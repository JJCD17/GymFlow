<?php

namespace App\Filament\Resources\Members\Pages;

use App\Actions\RegisterMembership;
use App\Filament\Resources\Members\MemberResource;
use App\Models\Member;
use App\Models\Plan;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $member = Member::create([
                'full_name' => $data['full_name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            app(RegisterMembership::class)->handle(
                member: $member,
                plan: Plan::findOrFail($data['plan_id']),
                amount: (float) $data['amount'],
                method: $data['method'],
                startsAt: $data['starts_at'],
            );

            return $member;
        });
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Cliente registrado con su membresía';
    }
}

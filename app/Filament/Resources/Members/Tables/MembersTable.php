<?php

namespace App\Filament\Resources\Members\Tables;

use App\Filament\Resources\Members\MemberResource;
use App\Models\Member;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['currentMembership.plan', 'lastCheckIn']))
            ->columns([
                TextColumn::make('full_name')
                    ->label('Cliente')
                    ->description(fn (Member $record) => $record->phone)
                    ->searchable(['full_name', 'phone'])
                    ->sortable(),
                TextColumn::make('membership_status')
                    ->label('Membresía')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'active' => 'Al corriente',
                        'expiring' => 'Por vencer',
                        'expired' => 'Vencida',
                        default => 'Sin membresía',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'expiring' => 'warning',
                        'expired' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('currentMembership.ends_at')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->description(fn (Member $record) => MemberResource::remainingLabel($record))
                    ->placeholder('—'),
                TextColumn::make('lastCheckIn.checked_in_at')
                    ->label('Última visita')
                    ->since()
                    ->placeholder('Nunca'),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado de membresía')
                    ->options([
                        'active' => 'Al corriente',
                        'expiring' => 'Por vencer',
                        'expired' => 'Vencidas',
                    ])
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->withMembershipStatus($data['value'])
                        : $query),
                Filter::make('sin_asistir')
                    ->label('Sin asistir')
                    ->query(fn ($query) => $query->inactiveSince(Auth::user()->gym->absenceThresholdDate())),
            ])
            ->recordActions([
                MemberResource::checkInAction(),
                ActionGroup::make([
                    MemberResource::renewAction(),
                    MemberResource::contactAction(),
                    EditAction::make()->label('Editar'),
                ]),
            ])
            ->recordUrl(fn (Member $record) => MemberResource::getUrl('view', ['record' => $record]))
            ->defaultSort('full_name')
            ->emptyStateHeading('Aún no tienes clientes')
            ->emptyStateDescription('Registra el primero para empezar.');
    }

}

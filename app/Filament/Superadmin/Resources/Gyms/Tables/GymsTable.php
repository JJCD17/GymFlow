<?php

namespace App\Filament\Superadmin\Resources\Gyms\Tables;

use App\Models\Gym;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GymsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['owner', 'subscriptions.plan']))
            ->columns([
                TextColumn::make('name')
                    ->label('Gimnasio')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('owner.name')
                    ->label('Dueño')
                    ->description(fn (Gym $record) => $record->owner?->username
                        ? "usuario: {$record->owner->username}"
                        : $record->owner?->email)
                    ->searchable()
                    ->placeholder('Sin dueño'),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('members_count')
                    ->label('Clientes')
                    ->counts('members')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('subscription')
                    ->label('Plan')
                    ->state(fn (Gym $record) => $record->latestSubscription()?->plan?->name ?? 'Sin plan')
                    ->description(fn (Gym $record) => self::subscriptionDescription($record))
                    ->toggleable(),
                // El estado no es un sí/no: importa distinguir a quién dimos
                // de baja nosotros de quien simplemente dejó de pagar.
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (Gym $record) => self::statusLabel($record))
                    ->color(fn (Gym $record) => self::statusColor($record)),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activos',
                        'suspended' => 'Suspendidos',
                        'expired' => 'Vencidos',
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'active' => $query->active(),
                        'suspended' => $query->suspended(),
                        'expired' => $query->whereNull('suspended_at')
                            ->whereDoesntHave('subscriptions', fn (Builder $sub) => $sub->active()),
                        default => $query,
                    }),
                Filter::make('expiring')
                    ->label('Vencen en 15 días')
                    ->query(fn (Builder $query) => $query->expiringWithin(15)),
            ], layout: FiltersLayout::AboveContent)
            ->recordActions([
                Action::make('toggleSuspension')
                    ->label(fn (Gym $record) => $record->isSuspended() ? 'Reactivar' : 'Suspender')
                    ->icon(fn (Gym $record) => $record->isSuspended() ? 'heroicon-o-play-circle' : 'heroicon-o-pause-circle')
                    ->color(fn (Gym $record) => $record->isSuspended() ? 'success' : 'warning')
                    ->requiresConfirmation()
                    ->modalDescription(fn (Gym $record) => $record->isSuspended()
                        ? self::reactivationDescription($record)
                        : 'Sus usuarios no podrán entrar hasta reactivarlo. No se borra ninguna información.')
                    ->action(fn (Gym $record) => $record->update([
                        'suspended_at' => $record->isSuspended() ? null : now(),
                    ])),
                EditAction::make()->label('Editar'),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('Aún no hay gimnasios')
            ->emptyStateDescription('Da de alta el primero para empezar.');
    }

    protected static function statusLabel(Gym $record): string
    {
        if ($record->isSuspended()) {
            return 'Suspendido';
        }

        return $record->hasActiveSubscription() ? 'Activo' : 'Vencido';
    }

    protected static function statusColor(Gym $record): string
    {
        if ($record->isSuspended()) {
            return 'warning';
        }

        return $record->hasActiveSubscription() ? 'success' : 'danger';
    }

    protected static function subscriptionDescription(Gym $record): string
    {
        $subscription = $record->latestSubscription();

        if (! $subscription) {
            return 'nunca ha contratado';
        }

        $fecha = $subscription->ends_at->translatedFormat('d/m/Y');

        if ($subscription->is_expired) {
            return "venció el {$fecha}";
        }

        $dias = $subscription->days_remaining;

        return $dias === 0
            ? 'vence hoy'
            : "vence el {$fecha} ({$dias} ".($dias === 1 ? 'día' : 'días').')';
    }

    protected static function reactivationDescription(Gym $record): string
    {
        return $record->hasActiveSubscription()
            ? 'Sus usuarios podrán volver a entrar al sistema.'
            : 'Su suscripción está vencida, así que seguirá sin poder entrar hasta que le registres una nueva.';
    }
}

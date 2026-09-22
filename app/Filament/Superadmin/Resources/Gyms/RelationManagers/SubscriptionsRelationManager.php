<?php

namespace App\Filament\Superadmin\Resources\Gyms\RelationManagers;

use App\Models\GymSubscription;
use App\Models\SubscriptionPlan;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    protected static ?string $title = 'Suscripciones';

    protected static ?string $modelLabel = 'suscripción';

    protected static ?string $pluralModelLabel = 'suscripciones';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subscription_plan_id')
                    ->label('Plan')
                    ->options(fn () => SubscriptionPlan::active()
                        ->orderBy('sort_order')
                        ->pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set, Get $get) => $set(
                        'ends_at',
                        self::calculateEndsAt($get('subscription_plan_id'), $get('starts_at')),
                    )),
                DatePicker::make('starts_at')
                    ->label('Inicia')
                    ->default(now())
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set, Get $get) => $set(
                        'ends_at',
                        self::calculateEndsAt($get('subscription_plan_id'), $get('starts_at')),
                    )),
                // Se calcula solo desde la duración del plan, pero queda
                // editable: un acuerdo puntual no debería obligar a inventar
                // un plan nuevo en el catálogo.
                DatePicker::make('ends_at')
                    ->label('Vence')
                    ->required()
                    ->helperText('Se calcula con la duración del plan. Puedes ajustarla si hubo un acuerdo distinto.'),
                Textarea::make('notes')
                    ->label('Notas')
                    ->placeholder('Cómo pagó, descuentos acordados, etc.')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn ($query) => $query->with('plan'))
            ->columns([
                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->description(fn (GymSubscription $record) => $record->notes),
                TextColumn::make('starts_at')
                    ->label('Inicia')
                    ->date('d/m/Y'),
                TextColumn::make('ends_at')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (GymSubscription $record) => self::statusLabel($record))
                    ->color(fn (GymSubscription $record) => self::statusColor($record)),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar suscripción')
                    ->modalHeading('Nueva suscripción')
                    ->mutateDataUsing(function (array $data): array {
                        $data['ends_at'] ??= self::calculateEndsAt(
                            $data['subscription_plan_id'],
                            $data['starts_at'],
                        );

                        return $data;
                    })
                    ->successNotificationTitle('Suscripción registrada'),
            ])
            ->recordActions([
                Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (GymSubscription $record) => $record->status === GymSubscription::STATUS_ACTIVE)
                    ->requiresConfirmation()
                    ->modalHeading('¿Cancelar esta suscripción?')
                    ->modalDescription('El gimnasio quedará fuera del sistema salvo que tenga otra vigente. El registro se conserva.')
                    ->action(fn (GymSubscription $record) => $record->update([
                        'status' => GymSubscription::STATUS_CANCELLED,
                    ])),
                EditAction::make()->label('Editar'),
            ])
            ->defaultSort('ends_at', 'desc')
            ->emptyStateHeading('Sin suscripciones')
            ->emptyStateDescription('Registra una para que el gimnasio pueda entrar al sistema.');
    }

    protected static function calculateEndsAt(mixed $planId, mixed $startsAt): ?string
    {
        if (! $planId || ! $startsAt) {
            return null;
        }

        $plan = SubscriptionPlan::find($planId);

        if (! $plan) {
            return null;
        }

        return Carbon::parse($startsAt)->addDays($plan->duration_days)->toDateString();
    }

    protected static function statusLabel(GymSubscription $record): string
    {
        if ($record->status === GymSubscription::STATUS_CANCELLED) {
            return 'Cancelada';
        }

        return $record->is_expired ? 'Vencida' : 'Vigente';
    }

    protected static function statusColor(GymSubscription $record): string
    {
        if ($record->status === GymSubscription::STATUS_CANCELLED) {
            return 'gray';
        }

        return $record->is_expired ? 'danger' : 'success';
    }
}

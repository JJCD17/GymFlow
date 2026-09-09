<?php

namespace App\Filament\Resources\Members\Tables;

use App\Actions\RegisterMembership;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Support\MessageTemplates;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;
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
                    ->description(fn (Member $record) => self::remainingLabel($record))
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
                    ->query(fn ($query) => $query->inactiveFor(Auth::user()->gym->inactivity_days)),
            ])
            ->recordActions([
                Action::make('checkIn')
                    ->label('Asistencia')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Member $record) {
                        $record->checkIns()->create(['checked_in_at' => now()]);

                        Notification::make()
                            ->title("Asistencia registrada para {$record->full_name}")
                            ->success()
                            ->send();
                    }),
                ActionGroup::make([
                    self::renewAction(),
                    self::contactAction(),
                    EditAction::make()->label('Editar'),
                ]),
            ])
            ->defaultSort('full_name')
            ->emptyStateHeading('Aún no tienes clientes')
            ->emptyStateDescription('Registra el primero para empezar.');
    }

    protected static function renewAction(): Action
    {
        return Action::make('renew')
            ->label('Renovar membresía')
            ->icon('heroicon-o-arrow-path')
            ->schema([
                Select::make('plan_id')
                    ->label('Plan')
                    ->options(fn () => Plan::active()->orderBy('sort_order')->pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set, $state) => $set('amount', Plan::find($state)?->price))
                    ->selectablePlaceholder(false),
                TextInput::make('amount')
                    ->label('Monto pagado')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Select::make('method')
                    ->label('Forma de pago')
                    ->options(Payment::METHODS)
                    ->default('cash')
                    ->selectablePlaceholder(false)
                    ->required(),
            ])
            ->action(function (Member $record, array $data) {
                $membership = app(RegisterMembership::class)->handle(
                    member: $record,
                    plan: Plan::findOrFail($data['plan_id']),
                    amount: (float) $data['amount'],
                    method: $data['method'],
                );

                Notification::make()
                    ->title('Membresía renovada')
                    ->body("Vence el {$membership->ends_at->format('d/m/Y')}.")
                    ->success()
                    ->send();
            });
    }

    protected static function contactAction(): Action
    {
        return Action::make('contact')
            ->label('Contactar por WhatsApp')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->visible(fn (Member $record) => filled($record->phone))
            ->url(fn (Member $record) => self::whatsappUrl($record), shouldOpenInNewTab: true);
    }

    protected static function whatsappUrl(Member $member): string
    {
        $gym = Auth::user()->gym;

        $template = match ($member->membership_status) {
            'expired' => $gym->message_expired,
            'expiring' => $gym->message_expiring,
            default => $gym->message_inactive,
        };

        $phone = preg_replace('/\D/', '', $member->phone);
        $text = rawurlencode(MessageTemplates::fill($template, $member));

        return "https://wa.me/{$phone}?text={$text}";
    }

    protected static function remainingLabel(Member $member): ?string
    {
        $membership = $member->currentMembership;

        if (! $membership) {
            return null;
        }

        $days = $membership->days_remaining;

        return match (true) {
            $days < 0 => 'Venció hace '.abs($days).' días',
            $days === 0 => 'Vence hoy',
            default => "Quedan {$days} días",
        };
    }
}

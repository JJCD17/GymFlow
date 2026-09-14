<?php

namespace App\Filament\Resources\Members;

use App\Actions\RegisterMembership;
use App\Filament\Resources\Members\Pages\CreateMember;
use App\Filament\Resources\Members\Pages\EditMember;
use App\Filament\Resources\Members\Pages\ListMembers;
use App\Filament\Resources\Members\Pages\ViewMember;
use App\Filament\Resources\Members\RelationManagers\CheckInsRelationManager;
use App\Filament\Resources\Members\RelationManagers\MembershipsRelationManager;
use App\Filament\Resources\Members\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\Members\Schemas\MemberForm;
use App\Filament\Resources\Members\Schemas\MemberInfolist;
use App\Filament\Resources\Members\Tables\MembersTable;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Support\MessageTemplates;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $modelLabel = 'cliente';

    protected static ?string $pluralModelLabel = 'clientes';

    protected static ?string $navigationLabel = 'Clientes';

    protected static string|\UnitEnum|null $navigationGroup = 'Día a día';

    protected static ?string $recordTitleAttribute = 'full_name';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return MemberForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MemberInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MembersTable::configure($table);
    }

    public static function checkInAction(): Action
    {
        return Action::make('checkIn')
            ->label('Asistencia')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->disabled(fn (Member $record) => $record->hasCheckedInToday())
            ->tooltip(fn (Member $record) => $record->hasCheckedInToday()
                ? 'Ya registró asistencia hoy'
                : null)
            ->action(function (Member $record, Component $livewire) {
                if ($record->hasCheckedInToday()) {
                    Notification::make()
                        ->title("{$record->full_name} ya registró asistencia hoy")
                        ->warning()
                        ->send();

                    return;
                }

                $record->checkIns()->create(['checked_in_at' => now()]);

                Notification::make()
                    ->title("Asistencia registrada para {$record->full_name}")
                    ->success()
                    ->send();

                if ($livewire instanceof ViewRecord) {
                    $livewire->js('$wire.$refresh()');
                }
            });
    }

    public static function renewAction(): Action
    {
        return Action::make('renew')
            ->label('Renovar membresía')
            ->icon('heroicon-o-arrow-path')
            ->modalHeading('Renovar membresía')
            ->modalSubmitActionLabel('Renovar')
            ->modalCancelActionLabel('Cancelar')
            ->closeModalByClickingAway(false)
            ->after(fn (Component $livewire) => static::refreshRelations($livewire))
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

    /**
     * Los historiales de la ficha son componentes aparte que no se enteran de
     * un cambio hecho desde el encabezado. Refrescar el componente no basta
     * cuando la acción viene de un modal, así que se recarga la página.
     */
    protected static function refreshRelations(Component $livewire): void
    {
        if ($livewire instanceof ViewRecord) {
            $livewire->redirect(
                static::getUrl('view', ['record' => $livewire->record]),
                navigate: false,
            );
        }
    }

    public static function contactAction(): Action
    {
        return Action::make('contact')
            ->label('Contactar por WhatsApp')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->visible(fn (Member $record) => filled($record->phone))
            ->url(fn (Member $record) => static::whatsappUrl($record), shouldOpenInNewTab: true);
    }

    public static function whatsappUrl(Member $member): string
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

    public static function remainingLabel(Member $member): ?string
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

    public static function getRelations(): array
    {
        return [
            MembershipsRelationManager::class,
            PaymentsRelationManager::class,
            CheckInsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembers::route('/'),
            'create' => CreateMember::route('/create'),
            'view' => ViewMember::route('/{record}'),
            'edit' => EditMember::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Members\Schemas;

use App\Models\Payment;
use App\Models\Plan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class MemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del cliente')
                    ->columns(2)
                    ->schema([
                        TextInput::make('full_name')
                            ->label('Nombre completo')
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->maxLength(255),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Membresía y pago')
                    ->description('Se registrará su primera membresía junto con el pago.')
                    ->columns(2)
                    ->visibleOn('create')
                    ->schema([
                        Select::make('plan_id')
                            ->label('Plan')
                            ->options(fn () => Plan::active()->orderBy('sort_order')->pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                $plan = Plan::find($state);
                                $set('amount', $plan?->price);
                            })
                            ->selectablePlaceholder(false),
                        DatePicker::make('starts_at')
                            ->label('Inicia el')
                            ->default(now())
                            ->live()
                            ->required()
                            ->helperText(fn (Get $get): string => self::expiryHint($get)),
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
                    ]),
            ]);
    }

    protected static function expiryHint(Get $get): string
    {
        $plan = Plan::find($get('plan_id'));
        $startsAt = $get('starts_at');

        if (! $plan || ! $startsAt) {
            return 'Elige un plan para ver la fecha de vencimiento.';
        }

        $endsAt = Carbon::parse($startsAt)->addDays($plan->duration_days);

        return "Vence el {$endsAt->translatedFormat('d \d\e F \d\e Y')}.";
    }
}

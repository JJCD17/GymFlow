<?php

namespace App\Filament\Superadmin\Resources\Gyms\Schemas;

use App\Models\SubscriptionPlan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class GymForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del gimnasio')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre del gimnasio')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, string $operation) {
                                if ($operation === 'create') {
                                    $set('owner_username', Str::slug((string) $state, ''));
                                }
                            }),
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(255),
                        Select::make('timezone')
                            ->label('Zona horaria')
                            ->options([
                                'America/Mexico_City' => 'Centro (Ciudad de México)',
                                'America/Hermosillo' => 'Pacífico (Hermosillo)',
                                'America/Tijuana' => 'Noroeste (Tijuana)',
                                'America/Cancun' => 'Sureste (Cancún)',
                            ])
                            ->default('America/Mexico_City')
                            ->selectablePlaceholder(false)
                            ->required(),
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->directory('gym-logos')
                            ->columnSpanFull(),
                    ]),

                Section::make('Suscripción')
                    ->description('Qué plan de GymFlow contrata. Sin una suscripción vigente sus usuarios no pueden entrar.')
                    ->columns(2)
                    ->visibleOn('create')
                    ->schema([
                        Select::make('subscription_plan_id')
                            ->label('Plan contratado')
                            ->options(fn () => SubscriptionPlan::active()
                                ->orderBy('sort_order')
                                ->pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->selectablePlaceholder(false)
                            ->default(fn () => SubscriptionPlan::active()->orderBy('sort_order')->value('id')),
                        DatePicker::make('subscription_starts_at')
                            ->label('Inicia')
                            ->default(now())
                            ->required()
                            ->live()
                            ->helperText(fn (Get $get): string => self::endsAtHint(
                                $get('subscription_plan_id'),
                                $get('subscription_starts_at'),
                            )),
                    ]),

                Section::make('Dueño del gimnasio')
                    ->description('Se creará su cuenta para que pueda entrar al sistema.')
                    ->columns(2)
                    ->visibleOn('create')
                    ->schema([
                        TextInput::make('owner_name')
                            ->label('Nombre del dueño')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('owner_username')
                            ->label('Usuario para entrar')
                            ->helperText('Con esto entra al sistema, sin escribir su correo.')
                            ->required()
                            ->unique('users', 'username')
                            ->alphaDash()
                            ->maxLength(50),
                        TextInput::make('owner_email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->unique('users', 'email')
                            ->maxLength(255),
                        TextInput::make('owner_password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->helperText('Mínimo 8 caracteres. Compártela con el dueño para su primer acceso.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function endsAtHint(mixed $planId, mixed $startsAt): string
    {
        $plan = $planId ? SubscriptionPlan::find($planId) : null;

        if (! $plan || ! $startsAt) {
            return 'De aquí sale la fecha de vencimiento.';
        }

        $endsAt = Carbon::parse($startsAt)->addDays($plan->duration_days);

        return "Vence el {$endsAt->translatedFormat('d \d\e F \d\e Y')}.";
    }
}

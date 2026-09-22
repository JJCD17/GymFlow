<?php

namespace App\Filament\Superadmin\Resources\SubscriptionPlans\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SubscriptionPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del plan')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->placeholder('Mensual')
                            ->required()
                            ->maxLength(255)
                            ->autofocus()
                            ->columnSpanFull(),
                        TextInput::make('price')
                            ->label('Precio')
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0)
                            ->required()
                            ->helperText('Lo que paga el gimnasio por este periodo.'),
                        TextInput::make('duration_days')
                            ->label('Duración')
                            ->numeric()
                            ->suffix('días')
                            ->minValue(1)
                            ->maxValue(3650)
                            ->required()
                            ->live(onBlur: true)
                            ->helperText(fn (Get $get): string => self::durationHint($get('duration_days'))),
                    ]),

                Section::make('Disponibilidad')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_trial')
                            ->label('Es periodo de prueba')
                            ->helperText('Marca los planes de cortesía para distinguirlos de los de paga.')
                            ->default(false),
                        Toggle::make('is_active')
                            ->label('Plan activo')
                            ->helperText('Si se apaga, deja de ofrecerse al contratar. Las suscripciones vigentes siguen igual.')
                            ->default(true),
                    ]),
            ]);
    }

    protected static function durationHint(mixed $days): string
    {
        $days = (int) $days;

        if ($days < 1) {
            return 'Días que dura la suscripción desde que inicia.';
        }

        $equivalent = match (true) {
            $days % 365 === 0 => self::pluralize($days / 365, 'año', 'años'),
            $days % 30 === 0 => self::pluralize($days / 30, 'mes', 'meses'),
            $days % 7 === 0 => self::pluralize($days / 7, 'semana', 'semanas'),
            default => null,
        };

        return $equivalent
            ? "Equivale a {$equivalent}."
            : 'Días que dura la suscripción desde que inicia.';
    }

    protected static function pluralize(float $count, string $singular, string $plural): string
    {
        $count = (int) $count;

        return $count === 1 ? "1 {$singular}" : "{$count} {$plural}";
    }
}

<?php

namespace App\Filament\Superadmin\Resources\Gyms\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                            ->maxLength(255),
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
                        Toggle::make('is_active')
                            ->label('Gimnasio activo')
                            ->helperText('Si se desactiva, sus usuarios no podrán entrar.')
                            ->default(true),
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->directory('gym-logos')
                            ->columnSpanFull(),
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
}

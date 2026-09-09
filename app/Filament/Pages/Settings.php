<?php

namespace App\Filament\Pages;

use App\Support\MessageTemplates;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $title = 'Ajustes';

    protected static ?string $navigationLabel = 'Ajustes';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Auth::user()->gym->only([
            'inactivity_days',
            'message_expiring',
            'message_expired',
            'message_inactive',
        ]));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Avisos de inasistencia')
                    ->description('Cuándo considerar que un cliente dejó de venir.')
                    ->schema([
                        TextInput::make('inactivity_days')
                            ->label('Días sin asistir')
                            ->helperText('Después de estos días sin venir, el cliente aparece en tus alertas.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(90)
                            ->required(),
                    ]),

                Section::make('Mensajes para tus clientes')
                    ->description($this->placeholderHint())
                    ->schema([
                        Textarea::make('message_expiring')
                            ->label('Membresía por vencer')
                            ->rows(3),
                        Textarea::make('message_expired')
                            ->label('Membresía vencida')
                            ->rows(3),
                        Textarea::make('message_inactive')
                            ->label('Cliente que no ha venido')
                            ->rows(3),
                    ]),
            ]);
    }

    protected function placeholderHint(): string
    {
        $keys = collect(MessageTemplates::PLACEHOLDERS)
            ->map(fn (string $description, string $key) => "$key ($description)")
            ->implode(' · ');

        return "Se reemplazan al enviar: $keys";
    }

    public function save(): void
    {
        Auth::user()->gym->update($this->form->getState());

        Notification::make()
            ->title('Ajustes guardados')
            ->success()
            ->send();
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\Gym;
use App\Support\MessageTemplates;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $title = 'Ajustes';

    protected static ?string $navigationLabel = 'Ajustes';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.settings';

    /** Cliente de ejemplo para la vista previa; el gimnasio es el real. */
    public const PREVIEW_CLIENT = 'Ana López';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Auth::user()->gym->only([
            'expiring_days',
            'inactivity_days',
            'closed_weekdays',
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
                // Cada sección: su ajuste arriba y, debajo, el mensaje que se
                // manda al cliente en ese caso. Las dos usan la misma rejilla
                // de dos columnas para que queden alineadas entre sí.
                Section::make('Membresías')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->columns(2)
                    ->schema([
                        TextInput::make('expiring_days')
                            ->label('Avisar antes de que venza')
                            ->suffix('días')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(30)
                            ->required()
                            ->helperText($this->note('Se marca "Por vencer" con estos días o menos.')),
                        Textarea::make('message_expiring')
                            ->label('Mensaje: membresía por vencer')
                            ->rows(3)
                            ->live(debounce: 400)
                            ->columnStart(1),
                        Textarea::make('message_expired')
                            ->label('Mensaje: membresía vencida')
                            ->rows(3)
                            ->live(debounce: 400),
                        Html::make(fn (Get $get) => $this->preview(
                            $get('message_expiring') ?: MessageTemplates::expiring(),
                            today()->addDays((int) ($get('expiring_days') ?: Gym::DEFAULT_EXPIRING_DAYS)),
                        )),
                        Html::make(fn (Get $get) => $this->preview(
                            $get('message_expired') ?: MessageTemplates::expired(),
                            today()->subDays(3),
                        )),
                        Html::make($this->note($this->variables()))
                            ->columnSpanFull(),
                    ]),

                Section::make('Asistencia')
                    ->icon(Heroicon::OutlinedClock)
                    ->columns(2)
                    ->schema([
                        TextInput::make('inactivity_days')
                            ->label('Ausente después de')
                            ->suffix('días')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(90)
                            ->required()
                            ->helperText($this->note('Sin venir estos días, aparece en tus alertas.')),
                        CheckboxList::make('closed_weekdays')
                            ->label('Días que no abres')
                            ->options([
                                1 => 'Lun',
                                2 => 'Mar',
                                3 => 'Mié',
                                4 => 'Jue',
                                5 => 'Vie',
                                6 => 'Sáb',
                                0 => 'Dom',
                            ])
                            ->columns(7)
                            ->helperText($this->note('No se cuentan como ausencia del cliente.')),
                        Textarea::make('message_inactive')
                            ->label('Mensaje: cliente que no ha venido')
                            ->rows(3)
                            ->live(debounce: 400)
                            ->columnSpanFull(),
                        Html::make(fn (Get $get) => $this->preview(
                            $get('message_inactive') ?: MessageTemplates::inactive(),
                            today()->addDays(15),
                        ))->columnSpanFull(),
                        Html::make($this->note($this->variables()))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /** Ícono y texto en una sola línea, con el acento del panel. */
    protected function note(string $text): HtmlString
    {
        $icon = svg('heroicon-o-information-circle', '', [
            'style' => 'width: 1rem; height: 1rem; flex-shrink: 0; color: var(--primary-500);',
        ])->toHtml();

        return new HtmlString(
            '<span style="display: flex; align-items: center; gap: 0.375rem;">'.$icon.'<span>'.e($text).'</span></span>'
        );
    }

    protected function preview(string $template, Carbon $endsAt): HtmlString
    {
        $message = MessageTemplates::render($template, self::PREVIEW_CLIENT, Auth::user()->gym->name, $endsAt);

        return new HtmlString(
            '<div class="gf-wa-preview">'
                .'<div class="gf-wa-preview-label">Así le llega a tu cliente (ejemplo con '.e(self::PREVIEW_CLIENT).')</div>'
                .'<div class="gf-wa-bubble">'.nl2br(e($message)).'</div>'
            .'</div>'
        );
    }

    protected function variables(): string
    {
        $parts = collect(MessageTemplates::PLACEHOLDERS)
            ->map(fn (string $description, string $key) => "{$key} por ".lcfirst($description))
            ->values()
            ->all();
        $last = array_pop($parts);

        return 'Las palabras entre llaves se llenan solas con los datos de cada cliente: '.implode(', ', $parts)." y {$last}.";
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

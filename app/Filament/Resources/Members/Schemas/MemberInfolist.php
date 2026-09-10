<?php

namespace App\Filament\Resources\Members\Schemas;

use App\Models\Member;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MemberInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('membership_status')
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
                        TextEntry::make('currentMembership.plan.name')
                            ->label('Plan actual')
                            ->placeholder('—'),
                        TextEntry::make('currentMembership.ends_at')
                            ->label('Vence')
                            ->date('d/m/Y')
                            ->helperText(fn (Member $record) => self::remainingLabel($record))
                            ->placeholder('—'),
                        TextEntry::make('lastCheckIn.checked_in_at')
                            ->label('Última visita')
                            ->since()
                            ->placeholder('Nunca'),
                    ]),

                Section::make('Datos del cliente')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('phone')
                            ->label('Teléfono')
                            ->placeholder('—'),
                        TextEntry::make('email')
                            ->label('Correo')
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label('Cliente desde')
                            ->date('d/m/Y'),
                        TextEntry::make('notes')
                            ->label('Notas')
                            ->placeholder('Sin notas')
                            ->columnSpanFull(),
                    ]),
            ]);
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

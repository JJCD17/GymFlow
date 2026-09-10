<?php

namespace App\Filament\Resources\Members\RelationManagers;

use App\Models\Payment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Pagos';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('paid_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->money('MXN')
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->label('Total')->money('MXN')),
                TextColumn::make('method')
                    ->label('Forma de pago')
                    ->formatStateUsing(fn (string $state) => Payment::METHODS[$state] ?? $state),
                TextColumn::make('membership.plan.name')
                    ->label('Concepto')
                    ->placeholder('Pago suelto'),
            ])
            ->defaultSort('paid_at', 'desc')
            ->emptyStateHeading('Sin pagos registrados');
    }
}

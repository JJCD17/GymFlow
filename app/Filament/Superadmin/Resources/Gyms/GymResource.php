<?php

namespace App\Filament\Superadmin\Resources\Gyms;

use App\Filament\Superadmin\Resources\Gyms\Pages\CreateGym;
use App\Filament\Superadmin\Resources\Gyms\Pages\EditGym;
use App\Filament\Superadmin\Resources\Gyms\Pages\ListGyms;
use App\Filament\Superadmin\Resources\Gyms\RelationManagers\SubscriptionsRelationManager;
use App\Filament\Superadmin\Resources\Gyms\Schemas\GymForm;
use App\Filament\Superadmin\Resources\Gyms\Tables\GymsTable;
use App\Models\Gym;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GymResource extends Resource
{
    protected static ?string $model = Gym::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $modelLabel = 'gimnasio';

    protected static ?string $pluralModelLabel = 'gimnasios';

    protected static ?string $navigationLabel = 'Gimnasios';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return GymForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GymsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SubscriptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGyms::route('/'),
            'create' => CreateGym::route('/create'),
            'edit' => EditGym::route('/{record}/edit'),
        ];
    }
}

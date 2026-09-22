<?php

namespace App\Filament\Superadmin\Resources\SubscriptionPlans;

use App\Filament\Superadmin\Resources\SubscriptionPlans\Pages\CreateSubscriptionPlan;
use App\Filament\Superadmin\Resources\SubscriptionPlans\Pages\EditSubscriptionPlan;
use App\Filament\Superadmin\Resources\SubscriptionPlans\Pages\ListSubscriptionPlans;
use App\Filament\Superadmin\Resources\SubscriptionPlans\Schemas\SubscriptionPlanForm;
use App\Filament\Superadmin\Resources\SubscriptionPlans\Tables\SubscriptionPlansTable;
use App\Models\SubscriptionPlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $modelLabel = 'plan de suscripción';

    protected static ?string $pluralModelLabel = 'planes de suscripción';

    protected static ?string $navigationLabel = 'Planes de suscripción';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return SubscriptionPlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionPlansTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptionPlans::route('/'),
            'create' => CreateSubscriptionPlan::route('/create'),
            'edit' => EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}

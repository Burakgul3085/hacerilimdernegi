<?php

namespace App\Filament\Resources\MembershipApplications;

use App\Filament\Concerns\AuthorizesByRole;
use App\Filament\Resources\MembershipApplications\Pages\EditMembershipApplication;
use App\Filament\Resources\MembershipApplications\Pages\ListMembershipApplications;
use App\Filament\Resources\MembershipApplications\Schemas\MembershipApplicationForm;
use App\Filament\Resources\MembershipApplications\Tables\MembershipApplicationsTable;
use App\Models\MembershipApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MembershipApplicationResource extends Resource
{
    use AuthorizesByRole;

    protected static ?string $model = MembershipApplication::class;

    protected static ?string $navigationLabel = 'Üyelik başvuruları';

    protected static ?string $modelLabel = 'başvuru';

    protected static ?string $pluralModelLabel = 'başvurular';

    protected static string|UnitEnum|null $navigationGroup = 'Başvurular';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    public static function canAccess(): bool
    {
        return static::editorRoles();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return MembershipApplicationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MembershipApplicationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembershipApplications::route('/'),
            'edit' => EditMembershipApplication::route('/{record}/edit'),
        ];
    }
}

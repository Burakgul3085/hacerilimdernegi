<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Concerns\AuthorizesByRole;
use App\Filament\Resources\AuditLogs\Pages\EditAuditLog;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Schemas\AuditLogForm;
use App\Filament\Resources\AuditLogs\Tables\AuditLogsTable;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AuditLogResource extends Resource
{
    use AuthorizesByRole;

    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationLabel = 'Denetim kayıtları';

    protected static ?string $modelLabel = 'kayıt';

    protected static ?string $pluralModelLabel = 'denetim kayıtları';

    protected static string|UnitEnum|null $navigationGroup = 'Yönetim';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    public static function canAccess(): bool
    {
        return static::superAdminOnly();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return AuditLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AuditLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'edit' => EditAuditLog::route('/{record}/edit'),
        ];
    }
}

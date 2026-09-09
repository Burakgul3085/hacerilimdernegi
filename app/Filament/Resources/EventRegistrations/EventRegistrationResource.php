<?php

namespace App\Filament\Resources\EventRegistrations;

use App\Filament\Concerns\AuthorizesByRole;
use App\Filament\Resources\EventRegistrations\Pages\CreateEventRegistration;
use App\Filament\Resources\EventRegistrations\Pages\EditEventRegistration;
use App\Filament\Resources\EventRegistrations\Pages\ListEventRegistrations;
use App\Filament\Resources\EventRegistrations\Schemas\EventRegistrationForm;
use App\Filament\Resources\EventRegistrations\Tables\EventRegistrationsTable;
use App\Models\EventRegistration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EventRegistrationResource extends Resource
{
    use AuthorizesByRole;

    protected static ?string $model = EventRegistration::class;

    protected static ?string $navigationLabel = 'Etkinlik kayıtları';

    protected static ?string $modelLabel = 'kayıt';

    protected static ?string $pluralModelLabel = 'kayıtlar';

    protected static string|UnitEnum|null $navigationGroup = 'Başvurular';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function canAccess(): bool
    {
        return static::editorRoles();
    }

    public static function form(Schema $schema): Schema
    {
        return EventRegistrationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventRegistrationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEventRegistrations::route('/'),
            'create' => CreateEventRegistration::route('/create'),
            'edit' => EditEventRegistration::route('/{record}/edit'),
        ];
    }
}

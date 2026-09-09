<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ContactMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Ad')->disabled(),
                TextInput::make('email')->label('E-posta')->disabled(),
                TextInput::make('phone')->label('Telefon')->disabled(),
                TextInput::make('subject')->label('Konu')->disabled(),
                Textarea::make('message')->label('Mesaj')->disabled()->rows(6),
                Toggle::make('is_read')->label('Okundu'),
            ]);
    }
}

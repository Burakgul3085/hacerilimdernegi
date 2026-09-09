<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Ad')->required(),
                TextInput::make('slug')->label('Bağlantı'),
                Select::make('type')
                    ->label('Tür')
                    ->options([
                        'post' => 'Yazı',
                        'announcement' => 'Duyuru',
                    ])
                    ->default('post'),
            ]);
    }
}

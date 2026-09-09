<?php

namespace App\Filament\Resources\EventRegistrations\Schemas;

use App\Enums\ApplicationStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EventRegistrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('event_id')->label('Etkinlik')->relationship('event', 'title')->required(),
                TextInput::make('name')->label('Ad soyad')->required(),
                TextInput::make('email')->label('E-posta')->email()->required(),
                TextInput::make('phone')->label('Telefon'),
                Textarea::make('notes')->label('Not'),
                Toggle::make('kvkk_accepted')->label('KVKK onayı'),
                Select::make('status')
                    ->label('Durum')
                    ->options(collect(ApplicationStatus::cases())->mapWithKeys(fn (ApplicationStatus $status) => [$status->value => $status->label()]))
                    ->required(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\MembershipApplications\Schemas;

use App\Enums\ApplicationStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MembershipApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Ad soyad')->required(),
                TextInput::make('email')->label('E-posta')->email()->required(),
                TextInput::make('phone')->label('Telefon'),
                TextInput::make('city')->label('Şehir'),
                Textarea::make('message')->label('Mesaj'),
                Toggle::make('kvkk_accepted')->label('KVKK'),
                Select::make('status')
                    ->label('Durum')
                    ->options(collect(ApplicationStatus::cases())->mapWithKeys(fn (ApplicationStatus $status) => [$status->value => $status->label()])),
            ]);
    }
}

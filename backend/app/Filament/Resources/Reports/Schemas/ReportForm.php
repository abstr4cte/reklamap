<?php

namespace App\Filament\Resources\Reports\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('advertisement_id')
                    ->label('ID Ogłoszenia')
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('reason')
                    ->label('Powód')
                    ->required(),
                Textarea::make('details')
                    ->label('Szczegóły')
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Oczekujące',
                        'reviewed' => 'Przejrzane',
                        'resolved' => 'Rozwiązane',
                    ])
                    ->required()
                    ->dehydrated(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Feedback\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FeedbackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Typ')
                    ->options([
                        'bug' => 'Błąd',
                        'feature' => 'Nowa funkcja',
                        'improvement' => 'Ulepszenie',
                        'other' => 'Inne',
                    ])
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(),
                TextInput::make('url')
                    ->label('URL')
                    ->url(),
                Textarea::make('message')
                    ->label('Wiadomość')
                    ->required(),
                TextInput::make('user_agent')
                    ->label('User Agent')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }
}

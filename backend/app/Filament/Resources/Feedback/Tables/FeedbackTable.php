<?php

namespace App\Filament\Resources\Feedback\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FeedbackTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Typ')
                    ->badge()
                    ->state(fn ($record) => match ($record->type) {
                        'bug' => 'Błąd',
                        'suggestion' => 'Sugestia',
                        'question' => 'Pytanie',
                        default => $record->type,
                    })
                    ->color(fn ($record) => match ($record->type) {
                        'bug' => 'danger',
                        'suggestion' => 'success',
                        'question' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('email')
                    ->label('Email')
                    ->sortable(),
                TextColumn::make('message')
                    ->label('Wiadomość')
                    ->limit(50),
                TextColumn::make('url')
                    ->label('URL')
                    ->limit(50),
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'bug' => 'Błąd',
                        'suggestion' => 'Sugestia',
                        'question' => 'Pytanie',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

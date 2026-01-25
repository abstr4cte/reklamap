<?php

namespace App\Filament\Resources\Feedback\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
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
                BadgeColumn::make('type')
                    ->label('Typ')
                    ->colors([
                        'danger' => 'bug',
                        'success' => 'feature',
                        'warning' => 'improvement',
                        'gray' => 'other',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'bug' => 'Błąd',
                        'feature' => 'Nowa funkcja',
                        'improvement' => 'Ulepszenie',
                        'other' => 'Inne',
                        default => $state,
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
                        'feature' => 'Nowa funkcja',
                        'improvement' => 'Ulepszenie',
                        'other' => 'Inne',
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

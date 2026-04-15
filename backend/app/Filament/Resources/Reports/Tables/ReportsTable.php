<?php

namespace App\Filament\Resources\Reports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('advertisement_id')
                    ->label('ID Ogłoszenia')
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('Powód')
                    ->badge()
                    ->state(fn ($record) => match ($record->reason) {
                        'incorrect_info' => 'Niepoprawne informacje',
                        'unavailable' => 'Już niedostępna',
                        'spam' => 'Spam / Oszustwo',
                        'other' => 'Inne',
                        default => $record->reason,
                    })
                    ->color(fn ($record) => match ($record->reason) {
                        'incorrect_info' => 'warning',
                        'unavailable' => 'gray',
                        'spam' => 'danger',
                        default => 'info',
                    })
                    ->sortable(),
                TextColumn::make('details')
                    ->label('Szczegóły')
                    ->limit(50),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn ($record) => match ($record->status) {
                        'pending' => 'Oczekujące',
                        'reviewed' => 'Przejrzane',
                        'resolved' => 'Rozwiązane',
                        default => $record->status,
                    })
                    ->color(fn ($record) => match ($record->status) {
                        'pending' => 'danger',
                        'reviewed' => 'warning',
                        'resolved' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Oczekujące',
                        'reviewed' => 'Przejrzane',
                        'resolved' => 'Rozwiązane',
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

<?php

namespace App\Filament\Resources\Reports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
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
                    ->sortable(),
                TextColumn::make('details')
                    ->label('Szczegóły')
                    ->limit(50),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'danger' => 'pending',
                        'warning' => 'reviewed',
                        'success' => 'resolved',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Oczekujące',
                        'reviewed' => 'Przejrzane',
                        'resolved' => 'Rozwiązane',
                        default => $state,
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

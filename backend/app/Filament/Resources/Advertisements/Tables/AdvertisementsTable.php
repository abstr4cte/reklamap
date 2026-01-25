<?php

namespace App\Filament\Resources\Advertisements\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AdvertisementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Tytuł')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('city')
                    ->label('Miasto')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Typ')
                    ->sortable(),
                BadgeColumn::make('is_verified')
                    ->label('Status')
                    ->colors([
                        'danger' => 0,
                        'success' => 1,
                    ])
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Zweryfikowane' : 'Niezweryfikowane'),
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_verified')
                    ->label('Status')
                    ->options([
                        true => 'Zweryfikowane',
                        false => 'Niezweryfikowane',
                    ]),
            ])
            ->recordActions([
                Action::make('verify')
                    ->label('Zweryfikuj')
                    ->action(fn ($record) => $record->update(['is_verified' => true]))
                    ->visible(fn ($record) => !$record->is_verified)
                    ->color('success'),
                Action::make('view')
                    ->label('Pokaż')
                    ->url(function ($record) {
                        $type = $record->type; // Typ już powinien być w bazie
                        $city = Str::slug($record->city);
                        $title = Str::slug($record->title);
                        $id = $record->id;
                        return config('app.frontend_url') . "/powierzchnia-reklamowa/{$type}/{$city}/{$title}-{$id}";
                    })
                    ->openUrlInNewTab()
                    ->color('info'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\BlogPosts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BlogPostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                ImageColumn::make('image')
                    ->label('Obrazek')
                    ->disk('public')
                    ->square(),
                TextColumn::make('title')
                    ->label('Tytuł')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'published',
                        'warning' => 'draft',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'published' => 'Opublikowany',
                        'draft' => 'Szkic',
                        default => $state,
                    }),
                BadgeColumn::make('category')
                    ->label('Kategoria')
                    ->colors([
                        'info' => 'poradniki',
                        'warning' => 'trendy',
                        // 'success' => 'case-study', // nieaktywna kategoria
                        'primary' => 'rynek-ooh',
                        'danger' => 'prawo-i-regulacje',
                        'gray' => 'lokalizacje',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'poradniki' => 'Poradniki',
                        'trendy' => 'Trendy',
                        // 'case-study' => 'Case Study', // nieaktywna kategoria
                        'rynek-ooh' => 'Rynek OOH',
                        'prawo-i-regulacje' => 'Prawo i regulacje',
                        'lokalizacje' => 'Lokalizacje',
                        default => $state,
                    }),
                TextColumn::make('excerpt')
                    ->label('Fragment')
                    ->limit(50),
                TextColumn::make('user.name')
                    ->label('Autor')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategoria')
                    ->options([
                        'poradniki' => 'Poradniki',
                        'trendy' => 'Trendy',
                        // 'case-study' => 'Case Study', // nieaktywna kategoria
                        'rynek-ooh' => 'Rynek OOH',
                        'prawo-i-regulacje' => 'Prawo i regulacje',
                        'lokalizacje' => 'Lokalizacje',
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

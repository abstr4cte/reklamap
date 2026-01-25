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
                    ->square(),
                TextColumn::make('title')
                    ->label('Tytuł')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->sortable(),
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
            ->filters([])
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

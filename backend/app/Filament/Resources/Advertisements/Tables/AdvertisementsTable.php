<?php

namespace App\Filament\Resources\Advertisements\Tables;

use App\Mail\AdRemovedMail;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
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
                        $city = Str::slug($record->city);
                        $title = Str::slug($record->title);
                        return config('app.frontend_url') . "/powierzchnia-reklamowa/{$record->type}/{$city}/{$title}-{$record->id}";
                    })
                    ->openUrlInNewTab()
                    ->color('info'),
                Action::make('delete')
                    ->label('Usuń')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->form([
                        Textarea::make('reason')
                            ->label('Powód usunięcia (opcjonalnie)')
                            ->placeholder('Opisz powód usunięcia — zostanie wysłany do właściciela ogłoszenia...')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data): void {
                        $reason = trim($data['reason'] ?? '');
                        if ($reason !== '' && $record->owner_email) {
                            Mail::to($record->owner_email)->send(new AdRemovedMail($record, $reason));
                        }
                        $record->delete();
                    })
                    ->requiresConfirmation(false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('delete')
                        ->label('Usuń zaznaczone')
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->form([
                            Textarea::make('reason')
                                ->label('Powód usunięcia (opcjonalnie)')
                                ->placeholder('Opisz powód usunięcia — zostanie wysłany do każdego właściciela...')
                                ->rows(3),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $reason = trim($data['reason'] ?? '');
                            foreach ($records as $record) {
                                if ($reason !== '' && $record->owner_email) {
                                    Mail::to($record->owner_email)->send(new AdRemovedMail($record, $reason));
                                }
                                $record->delete();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}

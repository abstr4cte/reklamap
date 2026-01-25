<?php

namespace App\Filament\Resources\Advertisements\Pages;

use App\Filament\Resources\Advertisements\AdvertisementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAdvertisements extends ListRecords
{
    protected static string $resource = AdvertisementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->orderBy('is_verified', 'asc')
            ->orderBy('id', 'desc');
    }
}

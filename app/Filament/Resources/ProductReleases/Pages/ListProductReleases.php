<?php

namespace App\Filament\Resources\ProductReleases\Pages;

use App\Filament\Resources\ProductReleases\ProductReleaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductReleases extends ListRecords
{
    protected static string $resource = ProductReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

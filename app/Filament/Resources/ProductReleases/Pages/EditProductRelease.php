<?php

namespace App\Filament\Resources\ProductReleases\Pages;

use App\Filament\Resources\ProductReleases\ProductReleaseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductRelease extends EditRecord
{
    protected static string $resource = ProductReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

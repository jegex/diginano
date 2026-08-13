<?php

namespace App\Filament\Resources\ProductReleases;

use App\Filament\Resources\ProductReleases\Pages\CreateProductRelease;
use App\Filament\Resources\ProductReleases\Pages\EditProductRelease;
use App\Filament\Resources\ProductReleases\Pages\ListProductReleases;
use App\Filament\Resources\ProductReleases\Schemas\ProductReleaseForm;
use App\Filament\Resources\ProductReleases\Tables\ProductReleasesTable;
use App\Models\ProductRelease;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductReleaseResource extends Resource
{
    protected static ?string $model = ProductRelease::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $navigationLabel = 'Releases';

    protected static ?string $modelLabel = 'Release';

    protected static ?string $pluralModelLabel = 'Releases';

    protected static ?string $recordTitleAttribute = 'version';

    public static function form(Schema $schema): Schema
    {
        return ProductReleaseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductReleasesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductReleases::route('/'),
            'create' => CreateProductRelease::route('/create'),
            'edit' => EditProductRelease::route('/{record}/edit'),
        ];
    }
}

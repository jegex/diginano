<?php

namespace App\Filament\Resources\ProductReleases\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProductReleaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('version')
                    ->required()
                    ->maxLength(50),
                Textarea::make('changelog')
                    ->label('Changelog')
                    ->rows(6)
                    ->placeholder('Catatan perubahan rilis ini…')
                    ->columnSpanFull(),
                FileUpload::make('file_path')
                    ->label('Release file')
                    ->directory('releases')
                    ->storeFileNamesIn('original_name')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}

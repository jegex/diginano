<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\LicenseLengthUnit;
use App\Enums\PlanStatus;
use App\Enums\PriceCategory;
use App\Enums\PricingScheme;
use App\Enums\RenewalIntervalUnit;
use App\Enums\TrialIntervalUnit;
use App\Enums\UsageAggregation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlansRelationManager extends RelationManager
{
    protected static string $relationship = 'plans';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                RichEditor::make('description')
                    ->label('Deskripsi')
                    ->maxLength(65535),
                Select::make('status')
                    ->label('Status')
                    ->options(PlanStatus::class)
                    ->required()
                    ->default(PlanStatus::Published),
                TextInput::make('sort')
                    ->label('Urutan')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('has_license_keys')
                    ->label('Terbitkan lisensi')
                    ->default(true),
                Toggle::make('is_license_limit_unlimited')
                    ->label('Aktivasi tanpa batas')
                    ->live()
                    ->default(false),
                TextInput::make('license_activation_limit')
                    ->label('Batas aktivasi')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->visible(fn (Get $get): bool => ! $get('is_license_limit_unlimited')),
                Toggle::make('is_license_length_unlimited')
                    ->label('Lisensi tanpa batas waktu')
                    ->live()
                    ->default(true),
                TextInput::make('license_length_value')
                    ->label('Masa berlaku lisensi (one-time)')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->visible(fn (Get $get): bool => ! $get('is_license_length_unlimited')),
                Select::make('license_length_unit')
                    ->label('Satuan masa berlaku')
                    ->options(LicenseLengthUnit::class)
                    ->required()
                    ->visible(fn (Get $get): bool => ! $get('is_license_length_unlimited')),

                Fieldset::make('Pricing')
                    ->label('Harga')
                    ->relationship('price')
                    ->schema([
                        Select::make('category')
                            ->label('Kategori')
                            ->options(PriceCategory::class)
                            ->required()
                            ->live(),
                        Select::make('scheme')
                            ->label('Skema')
                            ->options(PricingScheme::class)
                            ->required()
                            ->default(PricingScheme::Standard)
                            ->live(),
                        Select::make('usage_aggregation')
                            ->label('Agregasi pemakaian')
                            ->options(UsageAggregation::class)
                            ->nullable()
                            ->visible(fn (Get $get): bool => $get->enum('category', PriceCategory::class) === PriceCategory::Subscription)
                            ->live(),
                        TextInput::make('unit_price')
                            ->label('Harga per unit (USD)')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->visible(fn (Get $get): bool => in_array($get('category'), [PriceCategory::OneTime, PriceCategory::Subscription], true))
                            ->required(fn (Get $get): bool => in_array($get('category'), [PriceCategory::OneTime, PriceCategory::Subscription], true)),
                        TextInput::make('min_price')
                            ->label('Harga minimum (USD)')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->visible(fn (Get $get): bool => $get->enum('category', PriceCategory::class) === PriceCategory::Pwyw),
                        TextInput::make('suggested_price')
                            ->label('Harga yang disarankan (USD)')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->visible(fn (Get $get): bool => $get->enum('category', PriceCategory::class) === PriceCategory::Pwyw),
                        Toggle::make('setup_fee_enabled')
                            ->label('Biaya pengaturan')
                            ->live()
                            ->visible(fn (Get $get): bool => $get->enum('category', PriceCategory::class) === PriceCategory::Subscription),
                        TextInput::make('setup_fee')
                            ->label('Biaya pengaturan (USD)')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->required()
                            ->visible(fn (Get $get): bool => $get->enum('category', PriceCategory::class) === PriceCategory::Subscription && $get('setup_fee_enabled')),
                        TextInput::make('package_size')
                            ->label('Ukuran paket')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->visible(fn (Get $get): bool => $get->enum('scheme', PricingScheme::class) === PricingScheme::Package),
                        Repeater::make('tiers')
                            ->label('Tingkatan')
                            ->schema([
                                TextInput::make('last_unit')
                                    ->label('Sampai unit (kosong = tanpa batas)')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1),
                                TextInput::make('unit_price')
                                    ->label('Harga per unit (USD)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('$')
                                    ->required()
                                    ->formatStateUsing(fn (?int $state) => $state === null ? null : $state / 100)
                                    ->dehydrateStateUsing(fn ($state) => (int) round((float) $state * 100)),
                                TextInput::make('fixed_fee')
                                    ->label('Biaya tetap (USD)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('$')
                                    ->formatStateUsing(fn (?int $state) => $state === null ? null : $state / 100)
                                    ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? null : (int) round((float) $state * 100)),
                            ])
                            ->default([])
                            ->visible(fn (Get $get): bool => in_array($get('scheme'), [PricingScheme::Volume, PricingScheme::Graduated], true)),
                        Select::make('renewal_interval_unit')
                            ->label('Satuan periode')
                            ->options(RenewalIntervalUnit::class)
                            ->required()
                            ->visible(fn (Get $get): bool => $get->enum('category', PriceCategory::class) === PriceCategory::Subscription),
                        TextInput::make('renewal_interval_quantity')
                            ->label('Lama periode')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->visible(fn (Get $get): bool => $get->enum('category', PriceCategory::class) === PriceCategory::Subscription),
                        Select::make('trial_interval_unit')
                            ->label('Satuan uji coba')
                            ->options(TrialIntervalUnit::class)
                            ->nullable()
                            ->visible(fn (Get $get): bool => $get->enum('category', PriceCategory::class) === PriceCategory::Subscription),
                        TextInput::make('trial_interval_quantity')
                            ->label('Lama uji coba')
                            ->numeric()
                            ->minValue(1)
                            ->visible(fn (Get $get): bool => $get->enum('category', PriceCategory::class) === PriceCategory::Subscription),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('price.category')
                    ->label('Kategori')
                    ->badge(),
                TextColumn::make('price.scheme')
                    ->label('Skema'),
                TextColumn::make('price.unit_price')
                    ->label('Harga/unit')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('price.renewal_interval_quantity')
                    ->label('Periode')
                    ->formatStateUsing(fn ($state, $record) => $record->price?->isSubscription() ? "{$state} {$record->price?->periodLabel()}" : null)
                    ->placeholder('—'),
                TextColumn::make('price.usage_aggregation')
                    ->label('Pemakaian')
                    ->placeholder('—'),
                TextColumn::make('sort')
                    ->label('Urutan')
                    ->sortable(),
            ])
            ->defaultSort('sort')
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

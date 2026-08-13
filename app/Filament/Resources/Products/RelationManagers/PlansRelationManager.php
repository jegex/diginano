<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\BillingPeriod;
use App\Enums\PlanPricing;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
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
                    ->required()
                    ->maxLength(255),
                Select::make('pricing_mode')
                    ->label('Pricing mode')
                    ->options(PlanPricing::class)
                    ->required()
                    ->live(),
                TextInput::make('price')
                    ->label('Price (USD)')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->prefix('$'),
                TextInput::make('sale_price')
                    ->label('Sale price (USD)')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$')
                    ->requiredWith('sale_starts_at')
                    ->requiredWith('sale_ends_at'),
                DateTimePicker::make('sale_starts_at')
                    ->label('Sale starts at')
                    ->native(false),
                DateTimePicker::make('sale_ends_at')
                    ->label('Sale ends at')
                    ->native(false)
                    ->after('sale_starts_at'),
                Select::make('billing_period')
                    ->label('Billing period')
                    ->options(BillingPeriod::class)
                    ->visible(fn (Get $get): bool => $get('pricing_mode') === PlanPricing::Subscription)
                    ->required(fn (Get $get): bool => $get('pricing_mode') === PlanPricing::Subscription),
                TextInput::make('licenses_per_unit')
                    ->label('Licenses per unit')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('pricing_mode')
                    ->badge(),
                TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('sale_price')
                    ->label('Sale price')
                    ->money('USD')
                    ->placeholder('—'),
                TextColumn::make('billing_period')
                    ->placeholder('—'),
                TextColumn::make('licenses_per_unit')
                    ->label('Licenses/unit'),
            ])
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

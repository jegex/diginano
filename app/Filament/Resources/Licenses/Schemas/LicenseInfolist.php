<?php

namespace App\Filament\Resources\Licenses\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LicenseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('key')
                    ->copyable(),
                TextEntry::make('product.name'),
                TextEntry::make('user.email')
                    ->label('Customer'),
                TextEntry::make('plan.name')
                    ->label('Plan'),
                TextEntry::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                TextEntry::make('activation_limit')
                    ->label('Activation limit')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? 'Unlimited' : (string) $state),
                TextEntry::make('valid_until')
                    ->label('Valid until')
                    ->state(fn ($record) => $record->validUntil())
                    ->dateTime()
                    ->placeholder('Never'),
                TextEntry::make('created_at')
                    ->dateTime(),
            ])
            ->columns(2);
    }
}

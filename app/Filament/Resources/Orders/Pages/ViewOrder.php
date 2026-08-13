<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\OrderStatus;
use App\Services\OrderFinalizer;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve Pembayaran')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === OrderStatus::AwaitingConfirmation)
                ->action(function (Order $record): void {
                    app(OrderFinalizer::class)->finalize($record);

                    Notification::make()
                        ->title('Order dikompletkan.')
                        ->success()
                        ->send();

                    $this->redirect(OrderResource::getUrl('view', ['record' => $record]));
                }),
            Action::make('reject')
                ->label('Tolak Pembayaran')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === OrderStatus::AwaitingConfirmation)
                ->action(function (Order $record): void {
                    $record->update(['status' => OrderStatus::Cancelled]);

                    Notification::make()
                        ->title('Pembayaran ditolak, order dibatalkan.')
                        ->danger()
                        ->send();

                    $this->redirect(OrderResource::getUrl('view', ['record' => $record]));
                }),
        ];
    }
}

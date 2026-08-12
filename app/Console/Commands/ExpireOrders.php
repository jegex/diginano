<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\OrderStatus;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:expire')]
#[Description('Expire pending orders older than 24 hours')]
class ExpireOrders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expired = Order::query()
            ->where('status', OrderStatus::Pending)
            ->where('created_at', '<', now()->subHours(24))
            ->update(['status' => OrderStatus::Expired]);

        $this->info("Expired {$expired} order(s).");

        return self::SUCCESS;
    }
}

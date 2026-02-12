<?php

namespace App\Events;

use App\Models\Sale;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SaleCompleted implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Sale $sale
    ) {}
    public bool $afterCommit = true;
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel(
            'location.' . $this->sale->location_id
        );
    }

    public function broadcastAs(): string
    {
        return 'sale.completed';
    }

    public function broadcastWith(): array
    {
        $locationId = $this->sale->location_id;

        $todaySales = Sale::whereDate('created_at', today())
            ->where('location_id', $locationId)
            ->where('status', 'closed');

        return [
            'today' => [
                'salesTotal' => $todaySales->sum('total'),
                'transactions' => $todaySales->count(),
                'itemsSold' => DB::table('sale_items')
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->whereDate('sales.created_at', today())
                    ->where('sales.location_id', $locationId)
                    ->where('sales.status', 'closed')
                    ->sum('sale_items.quantity'),
                'cash' => round((float) $todaySales->sum('total'), 2),

            ],
        ];

    }
}

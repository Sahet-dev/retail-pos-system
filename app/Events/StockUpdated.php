<?php

namespace App\Events;

use App\Models\Stock;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Stock $stock
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel(
            'location.' . $this->stock->location_id
        );
    }

    public function broadcastAs(): string
    {
        return 'stock.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'product_id' => $this->stock->product_id,
            'location_id' => $this->stock->location_id,
            'quantity' => $this->stock->quantity,
            'min_threshold' => $this->stock->min_threshold,
        ];
    }
}

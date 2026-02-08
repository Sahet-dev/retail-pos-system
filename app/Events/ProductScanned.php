<?php

namespace App\Events;

use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\PrivateChannel;

class ProductScanned implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public StockMovement $movement
    ) {}

    public function broadcastOn()
    {
        return new PrivateChannel('location.' . $this->movement->location_id);
    }

    public function broadcastAs()
    {
        return 'product.scanned';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->movement->id,
            'time' => $this->movement->created_at->format('H:i'),
            'product' => $this->movement->product->name,
            'qty' => abs($this->movement->quantity),
            'price' => $this->movement->product->price,
            'kind' => 'item',
        ];
    }
}


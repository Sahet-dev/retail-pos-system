<?php

namespace App\Events;

use App\Models\StockMovement;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockMovementCreated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public StockMovement $movement) {}
    public bool $afterCommit = true;
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('location.' . $this->movement->location_id);
    }

    public function broadcastAs(): string
    {
        return 'stock.movement.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->movement->id,
            'product_id' => $this->movement->product_id,
            'quantity' => $this->movement->quantity,
            'type' => $this->movement->type,
            'reference_type' => $this->movement->reference_type,
            'time' => $this->movement->created_at->format('H:i'),
        ];
    }
}

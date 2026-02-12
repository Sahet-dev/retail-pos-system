<?php
namespace App\Events;

use App\Models\Sale;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaleCreated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public Sale $sale) {}
    public bool $afterCommit = true;
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('location.' . $this->sale->location_id);
    }

    public function broadcastAs(): string
    {
        return 'sale.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->sale->id,
            'location_id' => $this->sale->location_id,
            'status' => $this->sale->status,
            'total' => $this->sale->total,
            'started_at' => $this->sale->created_at->format('H:i'),
        ];
    }
}

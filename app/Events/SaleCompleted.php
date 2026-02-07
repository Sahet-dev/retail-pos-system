<?php

namespace App\Events;

use App\Models\Sale;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\PrivateChannel;

class SaleCompleted implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Sale $sale
    ) {}

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
        return [
            'sale_id' => $this->sale->id,
            'location_id' => $this->sale->location_id,
            'total' => $this->sale->total,
        ];
    }
}

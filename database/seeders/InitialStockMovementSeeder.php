<?php

namespace Database\Seeders;

use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InitialStockMovementSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Stock::all() as $stock) {
            StockMovement::create([
                'product_id' => $stock->product_id,
                'location_id' => $stock->location_id,
                'type' => 'inbound',
                'quantity' => $stock->quantity,
                'reference_type' => 'initial',
                'reference_id' => null,
            ]);
        }
    }
}

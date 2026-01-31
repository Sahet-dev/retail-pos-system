<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Location::where('type', 'store')->first();
        $warehouse = Location::where('type', 'warehouse')->first();

        foreach (Product::all() as $product) {
            Stock::create([
                'product_id' => $product->id,
                'location_id' => $store->id,
                'quantity' => 20,
                'min_threshold' => 5,
                'max_threshold' => 100,
            ]);

            Stock::create([
                'product_id' => $product->id,
                'location_id' => $warehouse->id,
                'quantity' => 100,
                'min_threshold' => 20,
                'max_threshold' => 500,
            ]);
        }
    }
}

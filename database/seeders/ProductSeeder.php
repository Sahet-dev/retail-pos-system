<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::insert([
            [
                'barcode' => '5941234567890',
                'name' => 'Milk 1L',
                'price' => 8.50,
                'cost_price' => 6.20,
                'active' => true,
            ],
            [
                'barcode' => '5949876543210',
                'name' => 'White Bread',
                'price' => 5.00,
                'cost_price' => 3.40,
                'active' => true,
            ],
            [
                'barcode' => '5945556667771',
                'name' => 'Eggs 10 pcs',
                'price' => 12.00,
                'cost_price' => 9.10,
                'active' => true,
            ],
        ]);
    }
}

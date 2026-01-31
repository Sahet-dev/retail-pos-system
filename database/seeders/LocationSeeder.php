<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Location::create([
            'name' => 'Main Store',
            'type' => 'store',
        ]);

        Location::create([
            'name' => 'Central Warehouse',
            'type' => 'warehouse',
        ]);
    }
}

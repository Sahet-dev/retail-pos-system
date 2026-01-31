<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function scan(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string',
            'location_id' => 'required|exists:locations,id',
        ]);

        // Find the product
        $product = Product::where('barcode', $request->barcode)
            ->where('active', true)
            ->firstOrFail();

        // Get or create an open sale for the location
        $sale = $request->sale_id
            ? Sale::findOrFail($request->sale_id)
            : Sale::create([
                'location_id' => $request->location_id,
                'status' => 'open',
                'total' => 0,
            ]);


        // Get or create the sale item
        $item = SaleItem::firstOrCreate(
            ['sale_id' => $sale->id, 'product_id' => $product->id],
            ['quantity' => 0, 'price' => $product->price]
        );

        // Increment quantity
        $item->increment('quantity');

        // Record stock movement
        StockMovement::create([
            'product_id' => $product->id,
            'location_id' => $sale->location_id,
            'type' => 'sale',
            'quantity' => 1,
            'reference_type' => 'sale',
            'reference_id' => $sale->id,
        ]);

        // Update stock
        $stock = Stock::where('product_id', $product->id)
            ->where('location_id', $sale->location_id)
            ->lockForUpdate() // prevents race conditions
            ->firstOrFail();

// Prevent selling if stock is zero
        if ($stock->quantity <= 0) {
            abort(422, 'Out of stock');
        }

// Decrement stock
        $stock->decrement('quantity');


        // Correct: sum on collection, not query
        $sale->total = $sale->items->sum(fn($i) => $i->quantity * $i->price);
        $sale->save();

        return response()->json([
            'sale_id' => $sale->id,
            'product' => $product->name,
            'quantity' => $item->quantity,
            'sale_total' => $sale->total,
            'stock_left' => $stock->quantity,
        ]);
    }
}

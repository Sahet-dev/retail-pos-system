<?php

namespace App\Http\Controllers;

use App\Events\ProductScanned;
use App\Events\SaleCreated;
use App\Events\StockMovementCreated;
use App\Events\StockUpdated;
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

        // Find product
        $product = Product::where('barcode', $request->barcode)
            ->where('active', true)
            ->firstOrFail();

        // Get or create open sale
        if ($request->sale_id) {
            $sale = Sale::findOrFail($request->sale_id);
        } else {
            $sale = Sale::create([
                'location_id' => $request->location_id,
                'status' => 'open',
                'total' => 0,
            ]);

            // Fire SaleCreated event
            event(new SaleCreated($sale));
        }

        // Get or create sale item
        $item = SaleItem::firstOrCreate(
            ['sale_id' => $sale->id, 'product_id' => $product->id],
            ['quantity' => 0, 'price' => $product->price]
        );

        // Increment quantity
        $item->increment('quantity');

        // Record stock movement
        $movement = StockMovement::create([
            'product_id' => $product->id,
            'location_id' => $sale->location_id,
            'type' => 'sale',
            'quantity' => 1,
            'reference_type' => 'sale',
            'reference_id' => $sale->id,
        ]);

        // Fire ProductScanned and StockMovementCreated events
        event(new ProductScanned($movement));
        event(new StockMovementCreated($movement));

        // Update stock
        $stock = Stock::where('product_id', $product->id)
            ->where('location_id', $sale->location_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($stock->quantity <= 0) {
            abort(422, 'Out of stock');
        }

        $stock->decrement('quantity');
        $stock->refresh();

        // Fire StockUpdated event
        event(new StockUpdated($stock));

        // Update sale total
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

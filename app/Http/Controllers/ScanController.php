<?php

namespace App\Http\Controllers;

use App\Events\ProductScanned;
use App\Events\StockMovementCreated;
use App\Events\StockUpdated;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScanController extends Controller
{
    public function scan(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string',
            'location_id' => 'required|exists:locations,id',
            'sale_id' => 'nullable|exists:sales,id',
        ]);

        $movement = null;
        $stock = null;
        $sale = null;
        $itemQuantity = 0;

        DB::transaction(function () use ($request, &$movement, &$stock, &$sale, &$itemQuantity) {

            // 1️⃣ Fetch active product
            $product = Product::where('barcode', $request->barcode)
                ->where('active', true)
                ->firstOrFail();

            // 2️⃣ Get or create open sale
            $sale = $request->sale_id
                ? Sale::findOrFail($request->sale_id)
                : Sale::create([
                    'location_id' => $request->location_id,
                    'status' => 'open',
                    'total' => 0,
                ]);

            if ($sale->status !== 'open') {
                abort(409, 'Sale is closed');
            }

            // 3️⃣ Get stock (lock if needed for concurrency)
            $stock = Stock::where('product_id', $product->id)
                ->where('location_id', $sale->location_id)
                ->lockForUpdate() // optional: remove if single POS terminal
                ->firstOrFail();

            if ($stock->quantity <= 0) {
                abort(422, 'Out of stock');
            }

            // 4️⃣ Decrement stock
            $stock->decrement('quantity');

            // 5️⃣ Update or insert sale item atomically
            $updated = SaleItem::updateOrInsert(
                ['sale_id' => $sale->id, 'product_id' => $product->id],
                [
                    'quantity' => DB::raw('COALESCE(quantity, 0) + 1'),
                    'price' => $product->price,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            // Fetch current quantity for UI
            $itemQuantity = SaleItem::where('sale_id', $sale->id)
                ->where('product_id', $product->id)
                ->value('quantity');

            // 6️⃣ Record stock movement
            $movement = StockMovement::create([
                'product_id' => $product->id,
                'location_id' => $sale->location_id,
                'type' => 'sale',
                'quantity' => -1,
                'reference_type' => 'sale',
                'reference_id' => $sale->id,
            ]);

            // 7️⃣ Update sale total incrementally
            $sale->increment('total', $product->price);

        }); // transaction ends

        // 8️⃣ Dispatch events asynchronously after commit
        DB::afterCommit(function () use ($movement, $stock) {
            ProductScanned::dispatch($movement);
            StockMovementCreated::dispatch($movement);
            StockUpdated::dispatch($stock);
        });

        return response()->json([
            'sale_id' => $sale->id,
            'product' => $movement->product->name,
            'quantity' => $itemQuantity,
            'sale_total' => $sale->total,
            'stock_left' => $stock->quantity,
        ]);
    }
}

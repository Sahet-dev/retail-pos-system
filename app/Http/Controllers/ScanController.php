<?php

namespace App\Http\Controllers;

use App\Events\ProductScanned;
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

        $now = now();

        return DB::transaction(function () use ($request, $now) {

            // 1️⃣ Get product (indexed barcode REQUIRED)
            $product = DB::table('products')
                ->select('id', 'name', 'price')
                ->where('barcode', $request->barcode)
                ->where('active', true)
                ->first();

            if (!$product) {
                abort(404, 'Product not found');
            }

            // 2️⃣ Get or create sale
            if ($request->sale_id) {
                $sale = DB::table('sales')
                    ->where('id', $request->sale_id)
                    ->where('status', 'open')
                    ->first();

                if (!$sale) {
                    abort(409, 'Sale closed');
                }

                $saleId = $sale->id;
            } else {
                $saleId = DB::table('sales')->insertGetId([
                    'location_id' => $request->location_id,
                    'status' => 'open',
                    'total' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // 3️⃣ Atomic stock decrement
            $affected = DB::table('stocks')
                ->where('product_id', $product->id)
                ->where('location_id', $request->location_id)
                ->where('quantity', '>', 0)
                ->decrement('quantity');

            if ($affected === 0) {
                abort(422, 'Out of stock');
            }

            // 4️⃣ Sale item upsert (no select)
            DB::statement("
    INSERT INTO sale_items (sale_id, product_id, quantity, price, created_at, updated_at)
    VALUES (?, ?, 1, ?, ?, ?)
    ON CONFLICT (sale_id, product_id)
    DO UPDATE SET
        quantity = sale_items.quantity + 1,
        updated_at = EXCLUDED.updated_at
", [
                $saleId,
                $product->id,
                $product->price,
                $now,
                $now
            ]);


            // 5️⃣ Get updated quantity (fast indexed read)
            $itemQuantity = DB::table('sale_items')
                ->where('sale_id', $saleId)
                ->where('product_id', $product->id)
                ->value('quantity');

            // 6️⃣ Insert stock movement
            DB::table('stock_movements')->insert([
                'product_id' => $product->id,
                'location_id' => $request->location_id,
                'type' => 'sale',
                'quantity' => -1,
                'reference_type' => 'sale',
                'reference_id' => $saleId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // 7️⃣ Increment sale total
            DB::table('sales')
                ->where('id', $saleId)
                ->increment('total', $product->price);

            // 8️⃣ Get remaining stock (single read)
            $stockLeft = DB::table('stocks')
                ->where('product_id', $product->id)
                ->where('location_id', $request->location_id)
                ->value('quantity');

            return response()->json([
                'sale_id'    => $saleId,
                'product'    => $product->name,
                'quantity'   => $itemQuantity,
                'sale_total' => DB::table('sales')->where('id', $saleId)->value('total'),
                'stock_left' => $stockLeft,
            ]);
        });
    }
}

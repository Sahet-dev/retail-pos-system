<?php

namespace App\Http\Controllers;

use App\Events\SaleCompleted;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function cash(Request $request, $saleId)
    {
        $request->validate([
            'cash_given' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request, $saleId) {

            $sale = Sale::with('items')->lockForUpdate()->findOrFail($saleId);

            if ($sale->status !== 'open') {
                abort(409, 'Sale already closed');
            }

            if ($request->cash_given < $sale->total) {
                abort(422, 'Insufficient cash');
            }

            $saleTotal = (float) $sale->total;
            $cashGiven = (float) $request->cash_given;
            $changeAmount = $cashGiven - $saleTotal;

            $sale->update([
                'status' => 'closed',
                'cash_given' => $cashGiven,
                'change_amount' => $changeAmount,
                'closed_at' => now(),
            ]);


            // 🔔 Realtime event (Reverb later)
            event(new SaleCompleted($sale));

            return response()->json([
                'message' => 'Sale completed',
                'total' => $saleTotal,
                'cash' => $cashGiven,
                'change_amount' => $changeAmount,
            ]);

        });
    }
}

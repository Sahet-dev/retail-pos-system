<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $locationId = 1;

        $today = Carbon::today();
        $date = request('date', $today->toDateString());

        $completedSales = Sale::where('status', 'closed')
            ->whereDate('closed_at', $date);

        $salesTotal = (clone $completedSales)->sum('total');
        $transactions = (clone $completedSales)->count();
        $itemsSold = SaleItem::whereIn('sale_id', $completedSales->pluck('id'))->sum('quantity');
        $cashTotal = (clone $completedSales)->whereNotNull('cash_given')->sum('total');

        $initialSales = Sale::where('location_id', $locationId)
            ->latest()
            ->take(20)
            ->get(['id', 'total', 'status', 'cash_given', 'closed_at']);

        $initialStockEvents = StockMovement::where('location_id', $locationId)
            ->latest()
            ->take(20)
            ->get(['product_id', 'quantity', 'type', 'reference_type']);


        $liveFeed = StockMovement::query()
            ->where('location_id', $locationId)
            ->where('type', 'sale')
            ->with('product')
            ->latest()
            ->take(30)
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'time' => $m->created_at->format('H:i'),
                    'product' => $m->product->name,
                    'qty' => abs($m->quantity),
                    'price' => $m->product->price,
                    'kind' => 'item',
                ];
            });


        return Inertia::render('Dashboard', [
            'todayStats' => [
                'salesTotal' => round($salesTotal, 2),
                'transactions' => $transactions,
                'itemsSold' => $itemsSold,
                'cash' => round($cashTotal, 2),
            ],
            'initialSales' => $initialSales,
            'initialStockEvents' => $initialStockEvents,
            'liveFeed' => $liveFeed,
        ]);
    }
}


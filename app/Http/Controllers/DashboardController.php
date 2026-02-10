<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
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


        $stockAlerts = Stock::query()
            ->where('location_id', $locationId)
            ->where(function ($q) {
                $q->where('quantity', '<', 0)
                    ->orWhere('quantity', '=', 0)
                    ->orWhereColumn('quantity', '<', 'min_threshold');

            })
            ->with('product:id,name')
            ->orderByRaw('quantity < 0 desc') // negative first
            ->orderBy('quantity')
            ->get()
            ->map(function ($stock) {
                if ($stock->quantity < 0) {
                    return [
                        'id' => $stock->id,
                        'type' => 'negative',
                        'name' => $stock->product->name,
                        'message' => "Negative stock ({$stock->quantity})",
                    ];
                }

                if ($stock->quantity === 0) {
                    return [
                        'id' => $stock->id,
                        'type' => 'out',
                        'name' => $stock->product->name,
                        'message' => 'Out of stock',
                    ];
                }

                return [
                    'id' => $stock->id,
                    'type' => 'low',
                    'name' => $stock->product->name,
                    'message' => "{$stock->quantity} left",
                ];
            });

        $topByQty = SaleItem::query()
            ->selectRaw('products.id, products.name, SUM(sale_items.quantity) as qty')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'closed')
            ->whereDate('sales.closed_at', $date)
            ->where('sales.location_id', $locationId)
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        $topByRevenue = SaleItem::query()
            ->selectRaw('
        products.id,
        products.name,
        SUM(sale_items.quantity * sale_items.price) as revenue
    ')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'closed')
            ->whereDate('sales.closed_at', $date)
            ->where('sales.location_id', $locationId)
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        $cashSalesQuery = Sale::query()
            ->where('status', 'closed')
            ->whereDate('closed_at', $date)
            ->where('location_id', $locationId)
            ->whereNotNull('cash_given');

        $cashExpected = (clone $cashSalesQuery)->sum('total');
        $cashSalesCount = (clone $cashSalesQuery)->count();

        $avgTicket = $cashSalesCount > 0
            ? round($cashExpected / $cashSalesCount, 2)
            : 0;

        $thresholdMinutes = 5;
        $now = Carbon::now();

        $openSales = Sale::query()
            ->where('location_id', $locationId)
            ->where('status', 'open')
            ->where('created_at', '<=', $now->copy()->subMinutes($thresholdMinutes))
            ->orderBy('created_at')
            ->get()
            ->map(function ($sale) use ($now) {
                return [
                    'id' => $sale->id,
                    'terminal' => $sale->terminal_name ?? 'POS', // optional
                    'minutes' => $sale->created_at->diffInMinutes($now),
                    'started_at' => $sale->created_at->format('H:i'),
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
            'stockAlerts' => $stockAlerts,
            'topProducts' => [
                'byQty' => $topByQty,
                'byRevenue' => $topByRevenue,
            ],
            'cashStats' => [
                'expected' => round($cashExpected, 2),
                'salesCount' => $cashSalesCount,
                'avgTicket' => $avgTicket,
            ],
            'openSales' => $openSales,

        ]);
    }
}


<?php

namespace App\Http\Services;

use App\Models\Sale;
use App\Models\SaleItem;

class DashboardService
{
    public function getTodayStats(int $locationId, string $date): array
    {
        $completedSales = Sale::query()
            ->where('status', 'closed')
            ->whereDate('closed_at', $date)
            ->where('location_id', $locationId);

        $salesTotal = (clone $completedSales)->sum('total');
        $transactions = (clone $completedSales)->count();

        $itemsSold = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'closed')
            ->whereDate('sales.closed_at', $date)
            ->where('sales.location_id', $locationId)
            ->sum('sale_items.quantity');

        $cash = (clone $completedSales)
            ->whereNotNull('cash_given')
            ->sum('total');

        return [
            'salesTotal' => round((float)$salesTotal, 2),
            'transactions' => (int)$transactions,
            'itemsSold' => (int)$itemsSold,
            'cash' => round((float)$cash, 2),
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    public function branchDashboard(int $branchId): array
    {
        $todayStart = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        $salesToday = Order::query()
            ->where('branch_id', $branchId)
            ->where('ordered_at', '>=', $todayStart)
            ->sum('grand_total');

        $salesMonth = Order::query()
            ->where('branch_id', $branchId)
            ->where('ordered_at', '>=', $monthStart)
            ->sum('grand_total');

        $ordersCount = Order::query()->where('branch_id', $branchId)->count();

        $topProducts = OrderItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as qty'))
            ->whereHas('order', fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('product_id')
            ->orderByDesc('qty')
            ->limit(5)
            ->with('product')
            ->get()
            ->map(fn ($row) => [
                'product_id' => $row->product_id,
                'name' => $row->product?->name,
                'sku' => $row->product?->sku,
                'quantity_sold' => (int) $row->qty,
            ]);

        $lowStock = Inventory::query()
            ->with('product')
            ->where('branch_id', $branchId)
            ->where('quantity', '<=', 10)
            ->orderBy('quantity')
            ->limit(10)
            ->get()
            ->map(fn ($inv) => [
                'product_id' => $inv->product_id,
                'name' => $inv->product?->name,
                'sku' => $inv->product?->sku,
                'quantity' => (int) $inv->quantity,
            ]);

        return [
            'total_sales_today' => (string) $salesToday,
            'total_sales_month' => (string) $salesMonth,
            'total_orders' => (int) $ordersCount,
            'top_products' => $topProducts,
            'low_stock' => $lowStock,
        ];
    }
}


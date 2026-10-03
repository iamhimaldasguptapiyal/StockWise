<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $lowStockQuery = StockLevel::query()
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->whereColumn('stock_levels.quantity', '<=', 'products.reorder_level');

        $stockValue = StockLevel::query()
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->sum(DB::raw('stock_levels.quantity * products.cost_price'));

        $lowStock = (clone $lowStockQuery)
            ->select('stock_levels.*')
            ->with(['product:id,sku,name,reorder_level', 'branch:id,name,code'])
            ->orderBy('stock_levels.quantity')
            ->limit(8)
            ->get();

        return response()->json([
            'totals' => [
                'products' => Product::count(),
                'branches' => Branch::count(),
                'units_on_hand' => (int) StockLevel::sum('quantity'),
                'stock_value' => round((float) $stockValue, 2),
                'low_stock_items' => (clone $lowStockQuery)->count(),
            ],
            'low_stock' => $lowStock,
            'by_branch' => Branch::withSum('stockLevels as total_units', 'quantity')->orderBy('name')->get(['id', 'name', 'code']),
            'recent_movements' => StockMovement::with(['product:id,sku,name', 'branch:id,name,code', 'user:id,name'])
                ->latest()
                ->latest('id')
                ->limit(8)
                ->get(),
        ]);
    }
}

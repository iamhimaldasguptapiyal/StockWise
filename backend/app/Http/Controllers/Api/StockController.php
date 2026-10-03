<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockMovementRequest;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StockController extends Controller
{
    public function __construct(private readonly StockService $stock)
    {
    }

    /**
     * Current quantity of every product at each branch.
     */
    public function levels(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'low_only' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $levels = StockLevel::query()
            ->select('stock_levels.*')
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->with(['product:id,sku,name,reorder_level', 'branch:id,name,code'])
            ->when($request->branch_id, fn ($q, $id) => $q->where('stock_levels.branch_id', $id))
            ->when($request->search, function ($q, $search) {
                $q->where(fn ($q) => $q->where('products.name', 'like', "%{$search}%")->orWhere('products.sku', 'like', "%{$search}%"));
            })
            ->when($request->boolean('low_only'), fn ($q) => $q->whereColumn('stock_levels.quantity', '<=', 'products.reorder_level'))
            ->orderBy('products.name')
            ->orderBy('stock_levels.branch_id')
            ->paginate($request->integer('per_page', 20));

        return response()->json($levels);
    }

    public function movements(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'product_id' => ['nullable', 'integer'],
            'type' => ['nullable', Rule::in(StockMovement::TYPES)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $movements = StockMovement::query()
            ->with(['product:id,sku,name', 'branch:id,name,code', 'user:id,name'])
            ->when($request->branch_id, fn ($q, $id) => $q->where('branch_id', $id))
            ->when($request->product_id, fn ($q, $id) => $q->where('product_id', $id))
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->latest()
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json($movements);
    }

    public function recordMovement(StockMovementRequest $request): JsonResponse
    {
        $data = $request->validated();

        // "out" reduces stock, so it is stored as a negative change.
        $change = $data['type'] === 'out' ? -abs($data['quantity']) : $data['quantity'];

        $movement = $this->stock->adjust(
            $data['product_id'],
            $data['branch_id'],
            $change,
            $data['type'],
            $request->user(),
            $data['reference'] ?? null,
            $data['note'] ?? null,
        );

        return response()->json($movement->load(['product:id,sku,name', 'branch:id,name', 'user:id,name']), 201);
    }

    public function transfer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'from_branch_id' => ['required', 'integer', 'exists:branches,id'],
            'to_branch_id' => ['required', 'integer', 'exists:branches,id', 'different:from_branch_id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        if (! $user->isAdmin() && (int) $data['from_branch_id'] !== (int) $user->branch_id) {
            return response()->json(['message' => 'You can only transfer stock out of your own branch.'], 403);
        }

        [$out, $in] = $this->stock->transfer(
            $data['product_id'],
            $data['from_branch_id'],
            $data['to_branch_id'],
            $data['quantity'],
            $user,
            $data['note'] ?? null,
        );

        return response()->json(['out' => $out, 'in' => $in], 201);
    }
}

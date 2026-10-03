<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private const SORTABLE = ['name', 'sku', 'sell_price', 'total_stock', 'created_at'];

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTABLE)],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $products = Product::query()
            ->with(['category:id,name', 'supplier:id,name'])
            ->withSum('stockLevels as total_stock', 'quantity')
            ->when($request->search, function ($query, $search) {
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
            })
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->supplier_id, fn ($q, $id) => $q->where('supplier_id', $id))
            ->orderBy($request->input('sort', 'name'), $request->input('direction', 'asc'))
            ->paginate($request->integer('per_page', 15));

        return response()->json($products);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return response()->json($product->load(['category', 'supplier']), 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load([
            'category',
            'supplier',
            'stockLevels.branch:id,name,code',
            'movements' => fn ($q) => $q->with(['branch:id,name', 'user:id,name'])->latest()->limit(20),
        ])->loadSum('stockLevels as total_stock', 'quantity');

        return response()->json($product);
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return response()->json($product->load(['category', 'supplier']));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }
}

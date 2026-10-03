<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Supplier::withCount('products')->orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $supplier = Supplier::create($this->validated($request));

        return response()->json($supplier, 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json($supplier->loadCount('products'));
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $supplier->update($this->validated($request, $supplier));

        return response()->json($supplier);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers')->ignore($supplier?->id)],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);
    }
}

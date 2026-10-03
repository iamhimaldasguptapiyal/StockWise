<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(): JsonResponse
    {
        $branches = Branch::withCount('users')
            ->withSum('stockLevels as total_units', 'quantity')
            ->orderBy('name')
            ->get();

        return response()->json($branches);
    }

    public function store(Request $request): JsonResponse
    {
        $branch = Branch::create($this->validated($request));

        return response()->json($branch, 201);
    }

    public function show(Branch $branch): JsonResponse
    {
        return response()->json($branch->loadCount('users')->loadSum('stockLevels as total_units', 'quantity'));
    }

    public function update(Request $request, Branch $branch): JsonResponse
    {
        $branch->update($this->validated($request, $branch));

        return response()->json($branch);
    }

    public function destroy(Branch $branch): JsonResponse
    {
        if ($branch->stockLevels()->where('quantity', '>', 0)->exists()) {
            return response()->json([
                'message' => 'This branch still holds stock. Transfer or adjust it to zero before deleting.',
            ], 409);
        }

        $branch->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, ?Branch $branch = null): array
    {
        if ($request->has('code')) {
            $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('branches')->ignore($branch?->id)],
            'code' => ['required', 'string', 'max:10', 'alpha_num', Rule::unique('branches')->ignore($branch?->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
        ]);
    }
}

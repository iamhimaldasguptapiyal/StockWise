<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(User::with('branch:id,name')->orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_STAFF])],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        $user = User::create($data);

        return response()->json($user->load('branch:id,name'), 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user->load('branch:id,name'));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', Password::min(8)->letters()->numbers()],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_STAFF])],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        if ($user->is($request->user()) && $data['role'] !== User::ROLE_ADMIN) {
            return response()->json(['message' => 'You cannot remove your own administrator role.'], 422);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json($user->load('branch:id,name'));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->is($request->user())) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(null, 204);
    }
}

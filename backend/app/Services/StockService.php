<?php

namespace App\Services;

use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single entry point for changing stock quantities so every change is
 * transactional, can never go negative, and is recorded in the movement log.
 */
class StockService
{
    /**
     * Apply a signed quantity change to one product at one branch.
     */
    public function adjust(
        int $productId,
        int $branchId,
        int $change,
        string $type,
        ?User $user,
        ?string $reference = null,
        ?string $note = null,
    ): StockMovement {
        return DB::transaction(fn () => $this->applyChange($productId, $branchId, $change, $type, $user, $reference, $note));
    }

    /**
     * Move stock between branches as one atomic operation.
     *
     * @return array{0: StockMovement, 1: StockMovement}
     */
    public function transfer(
        int $productId,
        int $fromBranchId,
        int $toBranchId,
        int $quantity,
        ?User $user,
        ?string $note = null,
    ): array {
        return DB::transaction(function () use ($productId, $fromBranchId, $toBranchId, $quantity, $user, $note) {
            $reference = 'TRF-'.now()->format('YmdHis').'-'.random_int(100, 999);

            return [
                $this->applyChange($productId, $fromBranchId, -$quantity, 'transfer_out', $user, $reference, $note),
                $this->applyChange($productId, $toBranchId, $quantity, 'transfer_in', $user, $reference, $note),
            ];
        });
    }

    private function applyChange(
        int $productId,
        int $branchId,
        int $change,
        string $type,
        ?User $user,
        ?string $reference,
        ?string $note,
    ): StockMovement {
        StockLevel::firstOrCreate(['product_id' => $productId, 'branch_id' => $branchId]);

        // Row lock prevents two concurrent requests from both selling the last unit.
        $level = StockLevel::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->lockForUpdate()
            ->first();

        $newQuantity = $level->quantity + $change;

        if ($newQuantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => "Insufficient stock: only {$level->quantity} unit(s) available at this branch.",
            ]);
        }

        $level->update(['quantity' => $newQuantity]);

        return StockMovement::create([
            'product_id' => $productId,
            'branch_id' => $branchId,
            'user_id' => $user?->id,
            'type' => $type,
            'quantity' => $change,
            'balance_after' => $newQuantity,
            'reference' => $reference,
            'note' => $note,
        ]);
    }
}

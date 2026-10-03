<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPES = ['in', 'out', 'adjustment', 'transfer_in', 'transfer_out'];

    protected $fillable = [
        'product_id',
        'branch_id',
        'user_id',
        'type',
        'quantity',
        'balance_after',
        'reference',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

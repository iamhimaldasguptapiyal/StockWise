<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = ['name', 'code', 'address', 'phone'];

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

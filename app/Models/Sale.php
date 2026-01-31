<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Sale extends Model
{
    protected $fillable = [
        'location_id',
        'total',
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    /**
     * Get total dynamically if not stored in DB
     */
    public function getTotalAttribute($value)
    {
        // If total column exists in DB, return it
        if (!is_null($value)) {
            return $value;
        }

        // Otherwise, calculate sum from items collection
        return $this->items->sum(fn($item) => $item->price * $item->quantity);
    }
}
